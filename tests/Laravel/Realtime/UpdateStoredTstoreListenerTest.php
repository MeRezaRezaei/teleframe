<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Realtime;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Laravel\Realtime\UpdateStoredTstoreListener;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageMedia;
use MeRezaRezaei\Teleframe\Store\ChannelRegistry;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * W9 plan item 15: the optional UpdateStored → tf_store_media bridge.
 *
 * The listener is a mirror-path PRESENCE projection: it indexes a
 * (account_id, bucket, msg_id) row the moment a data-channel document
 * lands, with Telegram-native identity filled lazily by the reconcile
 * walk or the raw bus path. These tests pin the opt-in surface: declared
 * data channel + document media ⇒ one row = one message (single-shot,
 * idempotent key); anything else ⇒ silent no-op, never a throw.
 */
final class UpdateStoredTstoreListenerTest extends TestCase
{
    private const ACCOUNT = 3;

    private MemoryStoreIndex $index;

    private UpdateStoredTstoreListener $listener;

    protected function setUp(): void
    {
        parent::setUp();

        $this->index = new MemoryStoreIndex;
        $this->listener = new UpdateStoredTstoreListener($this->index);
    }

    public function test_indexes_a_document_message_from_a_declared_data_channel(): void
    {
        $this->index->upsertBucket(self::ACCOUNT, 'data:assets', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        $message = $this->message(410, peerType: 3, peerId: 202, mediaCtor: 'messageMediaDocument');
        ($this->listener)(new UpdateStored($message, self::ACCOUNT));

        $row = $this->index->findMedia(self::ACCOUNT, 'data:assets', 410);
        $this->assertNotNull($row);
        $this->assertSame('document', $row['media_type']);
        $this->assertSame(0, $row['doc_id'], 'mirror defers doc identity; the reconcile walk fills it');
        $this->assertNull($row['content_sha256']);
    }

    public function test_single_shot_key_per_message(): void
    {
        $this->index->upsertBucket(self::ACCOUNT, 'data:assets', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        $message = $this->message(410, peerType: 3, peerId: 202, mediaCtor: 'messageMediaDocument');
        $stored = new UpdateStored($message, self::ACCOUNT);

        // Re-ingest / redelivery collapses onto the same natural key.
        ($this->listener)($stored);
        ($this->listener)($stored);

        $this->assertCount(1, $this->index->listMedia(self::ACCOUNT, 'data:assets'));
        $this->assertSame(410, $this->index->listMedia(self::ACCOUNT, 'data:assets')[0]['msg_id']);
    }

    public function test_silent_noop_for_non_document_or_foreign_peers(): void
    {
        $this->index->upsertBucket(self::ACCOUNT, 'data:assets', [
            'kind' => ChannelRegistry::KIND_DATA,
            'channel_id' => 202,
            'access_hash' => 77,
        ]);

        // Photo media → no index.
        ($this->listener)(new UpdateStored(
            $this->message(1, peerType: 3, peerId: 202, mediaCtor: 'messageMediaPhoto'),
            self::ACCOUNT,
        ));

        // Document on an undeclared channel → no index.
        ($this->listener)(new UpdateStored(
            $this->message(2, peerType: 3, peerId: 999, mediaCtor: 'messageMediaDocument'),
            self::ACCOUNT,
        ));

        // Document in a private chat peer (peer_type 1 ≠ channel) → no index.
        ($this->listener)(new UpdateStored(
            $this->message(3, peerType: 1, peerId: 202, mediaCtor: 'messageMediaDocument'),
            self::ACCOUNT,
        ));

        $this->assertSame([], $this->index->listMedia(self::ACCOUNT, 'data:assets'));
    }

    public function test_non_message_models_are_ignored_without_throw(): void
    {
        ($this->listener)(new UpdateStored(new ModelStub, self::ACCOUNT));
        $this->assertSame([], $this->index->listMedia(self::ACCOUNT, 'data:assets'));
    }

    private function message(int $id, int $peerType, int $peerId, string $mediaCtor): TfMessage
    {
        $message = new TfMessage([
            'id' => $id,
            'peer_type' => $peerType,
            'peer_id' => $peerId,
        ]);
        $message->setRelation('media', new TfMessageMedia(['constructor' => $mediaCtor]));

        return $message;
    }
}

/** Minimal non-message root the listener must tolerate (never throw). */
final class ModelStub extends Model {}
