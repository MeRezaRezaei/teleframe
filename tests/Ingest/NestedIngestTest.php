<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageFromId;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Phase C re-baseline of the plan Task-3 nested-payload ingest test onto the
 * curated dial: the canned updateNewMessage tree (message + peer refs +
 * entities + media) decomposes into tf_updates (keyed (account_id, seq,
 * position)) with its hand-authored children (pts, pts_count, message
 * routing) and the nested message lands in the sibling messages mirror.
 *
 * The payloads themselves live in HasNestedUpdateFixtures (shared 1:1 with
 * the Postgres mirror track, tests/Pg/FullMirrorPgTest).
 *
 * Curated truth: constructor = ctor name (no crc32), inline peer halves
 * (peer_type/peer_id), booleans as live wire-false columns, NO tl_data
 * JSONB. Ephemeral nodes (peerUser/peerChannel, messageMediaEmpty,
 * messageEntity*) and the identity sidecars (channel/user ctors) have no
 * curated ingest write surface — they are neither child rows nor JSONB.
 */
final class NestedIngestTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    private const OTHER_ACCOUNT = 8;

    private const CHANNEL_ID = self::FIXTURE_CHANNEL_ID;

    private const USER_ID = self::FIXTURE_USER_ID;

    private function ingestTree(int $accountId = self::ACCOUNT): TfUpdate
    {
        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);

        $root = $ingestor->ingest(self::updateNewMessagePayload(), $accountId);
        assert($root instanceof TfUpdate);

        return $root;
    }

    public function test_ingests_full_update_new_message_tree(): void
    {
        $root = $this->ingestTree();

        self::assertInstanceOf(TfUpdate::class, $root);
        self::assertSame('updateNewMessage', $root->getAttribute('constructor'));
        self::assertSame(self::ACCOUNT, (int) $root->getAttribute('account_id'));
        self::assertSame(0, (int) $root->getAttribute('seq'));
        self::assertSame(0, (int) $root->getAttribute('position'));

        // Pts facts land in the hand-authored children (row = fact existence).
        $stored = TfUpdate::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1349, (int) $stored->pts()->sole()->getAttribute('pts'));
        self::assertSame(1, (int) $stored->ptsCount()->sole()->getAttribute('pts_count'));

        // Nested message row: extracted columns + canonical inline peer
        // halves, no tl_data / message_text legacy extraction. The wire
        // message's peer is the outgoing channel (peerChannel).
        $message = TfMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1186, (int) $message->getAttribute('id'));
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->getAttribute('message'));
        self::assertSame(1724852400, (int) $message->getAttribute('date'));
        self::assertTrue((bool) $message->getAttribute('out'));
        self::assertSame(3, (int) $message->getAttribute('peer_type'), 'peerChannel normalizes to peer_type 3');
        self::assertSame(self::CHANNEL_ID, (int) $message->getAttribute('peer_id'), 'message.peer_id = canonical channel id');
        self::assertSame('message', $message->getAttribute('constructor'));

        // from_id is a 1:1 inline peer-pair child (tf_messages_from_id).
        $from = TfMessageFromId::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1, (int) $from->getAttribute('from_id_type'));
        self::assertSame(self::USER_ID, (int) $from->getAttribute('from_id_id'));

        // The tf_updates_message routing child points at the sibling.
        $linked = $stored->message()->sole();
        self::assertSame(3, (int) $linked->getAttribute('peer_type'));
        self::assertSame(self::CHANNEL_ID, (int) $linked->getAttribute('peer_id'));
        self::assertSame(1186, (int) $linked->getAttribute('message_id'));
    }

    public function test_ephemeral_nodes_and_identity_sidecars_store_nothing(): void
    {
        $this->ingestTree();

        // The ephemeral child surface is NOT written by the curated ingestor:
        // entities/media ride the wire ctor only. None of the curated
        // envelope/media tables gain rows from this payload.
        self::assertSame(0, DB::table('tf_messages_entities')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(0, DB::table('tf_messages_media')->where('account_id', self::ACCOUNT)->count());

        // channel/user sidecars have no curated ingest surface — null roots,
        // no identity rows.
        self::assertNull((new UpdateIngestor)->ingest(self::channelPayload(), self::ACCOUNT));
        self::assertNull((new UpdateIngestor)->ingest(self::userPayload(), self::ACCOUNT));
        self::assertSame(0, DB::table('tf_channels')->where('account_id', self::ACCOUNT)->count());
        self::assertSame(0, DB::table('tf_users')->where('account_id', self::ACCOUNT)->count());
    }

    public function test_update_stored_event_fires_with_committed_root_model(): void
    {
        Event::fake([UpdateStored::class]);

        $root = $this->app->make(UpdateIngestor::class)->ingest(self::updateNewMessagePayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
        Event::assertDispatched(UpdateStored::class, function (UpdateStored $event) use ($root): bool {
            return $event->model instanceof TfUpdate
                && $event->model->is($root)
                && $event->accountId === self::ACCOUNT;
        });
    }

    public function test_tenants_isolate_nested_trees(): void
    {
        $rootA = $this->ingestTree(self::ACCOUNT);

        // Same Telegram entities under account 8: tenant-scoped rows keyed
        // by (account_id, seq, position) / (account_id, id).
        $ingestor = new UpdateIngestor;
        $ingestor->ingest(self::channelPayload(), self::OTHER_ACCOUNT);
        $ingestor->ingest(self::userPayload(), self::OTHER_ACCOUNT);
        $rootB = $ingestor->ingest(self::updateNewMessagePayload(), self::OTHER_ACCOUNT);

        self::assertSame(2, TfUpdate::acrossAccounts()->count(), 'one update row per tenant');
        self::assertSame(2, TfMessage::acrossAccounts()->count(), 'one message row per tenant');

        // Account 7's view is untouched by the account 8 ingest.
        $messageA = TfMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1186, (int) $messageA->getAttribute('id'));
        self::assertSame(self::ACCOUNT, (int) $rootA->getAttribute('account_id'));
        self::assertSame(self::OTHER_ACCOUNT, (int) $rootB->getAttribute('account_id'));
        self::assertSame(1, TfUpdate::forAccount(self::ACCOUNT)->count());
        self::assertSame(1, TfUpdate::forAccount(self::OTHER_ACCOUNT)->count());
    }

    public function test_full_re_ingest_keeps_counts_stable(): void
    {
        $this->ingestTree();
        $this->ingestTree();

        // The curated update surface is keyed (account_id, seq, position) —
        // a lone update always writes the (0, 0) key, so re-ingest UPSERTS
        // instead of appending. Both update and message rows stay stable.
        self::assertSame(1, TfUpdate::acrossAccounts()->count(), 'update row upsert-stable on (account_id, seq, position)');
        self::assertSame(1, TfMessage::acrossAccounts()->count(), 'message row upsert-stable on (account_id, id)');

        // Third re-ingest: still stable.
        $this->ingestTree();
        self::assertSame(1, TfUpdate::acrossAccounts()->count());
        self::assertSame(1, TfMessage::acrossAccounts()->count());
    }

    public function test_root_without_constructor_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'_'");
        (new UpdateIngestor)->ingest(['pts' => 1, 'pts_count' => 1], self::ACCOUNT);
    }
}
