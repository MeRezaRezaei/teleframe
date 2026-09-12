<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Plan Task 3: nested-payload ingest of the canned updateNewMessage#1f2b0afd
 * tree (message + peer refs + entities + media) plus the difference-stream
 * sidecar entities (channel + user objects), all through the same generic
 * ingest() surface onto the TDLib-style domain tables.
 *
 * The payloads themselves live in HasNestedUpdateFixtures (shared 1:1 with
 * the Postgres mirror track, tests/Pg/FullMirrorPgTest).
 *
 * Domain truth: only classified ctors persist (update→tf_updates,
 * message→tf_messages, channel→tf_channels, user→tf_users). Ephemeral
 * nodes (peerUser/peerChannel, messageMediaEmpty, messageEntity*,
 * chatPhotoEmpty) ride along inside their parent's tl_data JSONB — there
 * are no child rows, no peer tables, no media/entity tables.
 */
final class NestedIngestTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    private const CHANNEL_ID = self::FIXTURE_CHANNEL_ID;

    private const USER_ID = self::FIXTURE_USER_ID;

    private function ingestTree(int $accountId = self::ACCOUNT): TlUpdate
    {
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);

        $root = $ingestor->ingest(self::updateNewMessagePayload(), $accountId);
        assert($root instanceof TlUpdate);

        return $root;
    }

    public function test_ingests_full_update_new_message_tree(): void
    {
        $root = $this->ingestTree();

        self::assertInstanceOf(TlUpdate::class, $root);

        // Root update row: verbatim pts cols + constructor marker.
        self::assertSame(0x1f2b0afd, (int) $root->constructor_id);
        self::assertSame(self::ACCOUNT, (int) $root->account_id);
        self::assertSame(1349, (int) $root->pts);
        self::assertSame(1, (int) $root->pts_count);
        self::assertSame('updateNewMessage', $root->tl_data['_']);

        // Message row: extracted columns + canonical peer longs + JSONB.
        $message = TlMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(1186, (int) $message->message_id);
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message_text);
        self::assertSame(1724852400, (int) $message->date);
        self::assertTrue((bool) $message->is_out);
        self::assertSame(PeerIdTool::userLong(self::USER_ID), (int) $message->from_id, 'message.from_id = canonical user long');
        self::assertSame(PeerIdTool::channelLong(self::CHANNEL_ID), (int) $message->peer_id, 'message.peer_id = canonical channel long');
        self::assertSame('message', $message->tl_data['_']);
        self::assertSame(0x7600b9d3, (int) $message->constructor_id, 'message#7600b9d3');

        // Ephemeral peer/media/entity nodes have no tables — but they ride
        // inside the message JSONB verbatim.
        $entities = $message->tl_data['entities'] ?? [];
        self::assertCount(3, $entities);
        self::assertSame('messageEntityBold', $entities[0]['_']);
        self::assertSame('messageEntityUrl', $entities[1]['_']);
        self::assertSame('messageEntityMentionName', $entities[2]['_']);
        self::assertSame('peerUser', $message->tl_data['from_id']['_']);
        self::assertSame('peerChannel', $message->tl_data['peer_id']['_']);
        self::assertSame('messageMediaEmpty', $message->tl_data['media']['_']);

        // Channel sidecar: native id PK + verbatim title + flags.
        $channel = TlChannel::forAccount(self::ACCOUNT)->sole();
        self::assertSame(self::CHANNEL_ID, (int) $channel->id);
        self::assertSame('Teleframe Café', $channel->title);
        self::assertTrue((bool) $channel->is_verified);
        self::assertTrue((bool) $channel->is_megagroup);

        // User sidecar: native id PK + verbatim name.
        $user = TlUser::forAccount(self::ACCOUNT)->sole();
        self::assertSame(self::USER_ID, (int) $user->id);
        self::assertSame('Reza', $user->first_name);
    }

    public function test_entities_preserve_vector_order_in_jsonb(): void
    {
        $this->ingestTree();

        $message = TlMessage::forAccount(self::ACCOUNT)->sole();
        $entities = $message->tl_data['entities'] ?? [];

        self::assertCount(3, $entities);
        self::assertSame([0, 6, 33], array_column($entities, 'offset'), 'wire vector order preserved in JSONB');
        self::assertSame([5, 21, 5], array_column($entities, 'length'));
        self::assertSame(self::USER_ID, $entities[2]['user_id']);
    }

    public function test_update_stored_event_fires_with_committed_root_model(): void
    {
        Event::fake([UpdateStored::class]);

        $root = $this->app->make(UpdateIngestor::class)->ingest(self::updateNewMessagePayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
        Event::assertDispatched(UpdateStored::class, function (UpdateStored $event) use ($root): bool {
            return $event->model instanceof TlUpdate
                && $event->model->is($root)
                && $event->accountId === self::ACCOUNT;
        });
    }

    public function test_tenants_isolate_nested_trees(): void
    {
        $rootA = $this->ingestTree(self::ACCOUNT);

        // Same Telegram entities under account 8: tenant-scoped rows with
        // tenant-local surrogate ids for messages/updates.
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::channelPayload(), 8);
        $ingestor->ingest(self::userPayload(), 8);
        $rootB = $ingestor->ingest(self::updateNewMessagePayload(), 8);

        // Updates + messages are per-tenant: two rows each side.
        self::assertSame(2, TlUpdate::acrossAccounts()->count());
        self::assertSame(2, TlMessage::acrossAccounts()->count());

        // Global-ID sidecars are per-tenant too (composite PK): two rows each.
        self::assertSame(2, TlChannel::acrossAccounts()->count(), 'two tenant rows for one Telegram channel');
        self::assertSame(2, TlUser::acrossAccounts()->count(), 'two tenant rows for one Telegram user');

        // Account 7's view is untouched by the account 8 ingest.
        $messageA = TlMessage::forAccount(self::ACCOUNT)->sole();
        self::assertSame(PeerIdTool::channelLong(self::CHANNEL_ID), (int) $messageA->peer_id, 'account 7 peer_id = canonical channel long');
        self::assertSame(1186, (int) $messageA->message_id);
        // Surrogate-key rows (updates/messages) carry no stable native id —
        // tenant isolation is proven by per-account scoping, not key compare
        // (TlAnchorModel::$incrementing=false leaves sqlite keys unfilled).
        self::assertSame(self::ACCOUNT, (int) $rootA->account_id);
        self::assertSame(8, (int) $rootB->account_id);
        self::assertSame(1, TlUpdate::forAccount(self::ACCOUNT)->count());
        self::assertSame(1, TlUpdate::forAccount(8)->count());
    }

    public function test_full_re_ingest_keeps_counts_stable(): void
    {
        $this->ingestTree();
        $this->ingestTree();

        // tf_updates is an append-only event log (BIGSERIAL PK, spec §3.6):
        // each ingest appends one row. Entity tables upsert-stable.
        self::assertSame(2, TlUpdate::acrossAccounts()->count(), 'update log appends one row per ingest');
        self::assertSame(1, TlMessage::acrossAccounts()->count(), 'message row upsert-stable');
        self::assertSame(1, TlChannel::acrossAccounts()->count(), 'channel row upsert-stable');
        self::assertSame(1, TlUser::acrossAccounts()->count(), 'user row upsert-stable');

        // Third re-ingest: entities still stable, log grows by exactly one.
        $this->ingestTree();
        self::assertSame(3, TlUpdate::acrossAccounts()->count());
        self::assertSame(1, TlMessage::acrossAccounts()->count());
        self::assertSame(1, TlChannel::acrossAccounts()->count());
        self::assertSame(1, TlUser::acrossAccounts()->count());
    }

    public function test_root_without_constructor_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'_'");
        (new UpdateIngestor())->ingest(['pts' => 1, 'pts_count' => 1], self::ACCOUNT);
    }
}
