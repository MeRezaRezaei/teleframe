<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatPhotoChatPhotoEmpty;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityBold;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityMentionName;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageEntityMessageEntityUrl;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaEmpty;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessageEntities;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerPeerChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerPeerUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdate;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserUser;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\HasNestedUpdateFixtures;

/**
 * Plan Task 3: recursive relational write of a NESTED payload — the full
 * canned updateNewMessage#1f2b0afd tree (message → peer refs, entities
 * vector-of-objects → child rows with idx ordering, media nesting) plus the
 * difference-stream sidecar entities (channel + user objects) that MTProto
 * delivers alongside, all through the same generic ingest() surface.
 *
 * The payloads themselves live in HasNestedUpdateFixtures (shared 1:1 with
 * the Postgres mirror track, tests/Pg/FullMirrorPgTest).
 *
 * Write order satisfies IMMEDIATE FKs on sqlite (children before parents;
 * anchor before instance; child rows after their parent instance), tenants
 * are isolated per account, re-ingest is idempotent, and UpdateStored fires
 * with the committed root model.
 */
final class NestedIngestTest extends IngestTestCase
{
    use HasNestedUpdateFixtures;

    private const ACCOUNT = self::FIXTURE_ACCOUNT;

    private const CHANNEL_ID = self::FIXTURE_CHANNEL_ID;

    private const USER_ID = self::FIXTURE_USER_ID;

    private function ingestTree(int $accountId = self::ACCOUNT): TlUpdateUpdateNewMessage
    {
        $ingestor = new UpdateIngestor();
        $ingestor->ingest(self::channelPayload(), $accountId);
        $ingestor->ingest(self::userPayload(), $accountId);

        return $ingestor->ingest(self::updateNewMessagePayload(), $accountId);
    }

    public function test_ingests_full_update_new_message_tree(): void
    {
        $root = $this->ingestTree();

        self::assertInstanceOf(TlUpdateUpdateNewMessage::class, $root);

        // Root anchor + instance: update namespace, tenant, verbatim pts cols.
        $anchor = TlUpdate::query()->sole();
        self::assertIsInt((int) $anchor->id);
        self::assertSame(0x1f2b0afd, $anchor->constructor_id);
        self::assertSame('updateNewMessage', $anchor->constructor_name);
        self::assertSame(self::ACCOUNT, (int) $anchor->account_id);
        self::assertSame($anchor->id, $root->id);
        self::assertSame(1349, $root->pts);
        self::assertSame(1, $root->pts_count);

        // Message namespace: anchor + verbatim instance columns.
        $message = TlMessageMessage::query()->sole();
        self::assertSame(1186, $message->tl_id);
        self::assertSame('Check https://t.me/teleframe from @Reza', $message->message);
        self::assertSame(1724852400, $message->date);
        self::assertTrue($message->out);
        self::assertSame($root->message, (int) $message->id, 'root.message ref → the message instance');

        // Ref columns carry the canonical peer longs (T1.2); the walker's
        // peer child rows persist alongside with the same identity.
        $fromPeer = TlPeerPeerUser::query()->sole();
        self::assertSame(self::USER_ID, $fromPeer->user_id);
        self::assertSame(PeerIdTool::userLong(self::USER_ID), (int) $message->from_id, 'message.from_id = canonical user long');
        $chanPeer = TlPeerPeerChannel::query()->where('constructor_name', 'peerChannel')->sole();
        self::assertSame(self::CHANNEL_ID, $chanPeer->channel_id);
        self::assertSame(PeerIdTool::channelLong(self::CHANNEL_ID), (int) $message->peer_id, 'message.peer_id = canonical channel long');
        self::assertSame(2, TlPeer::query()->count(), 'peer anchors for both refs');
        self::assertSame('peerChannel', TlPeer::query()->where('id', $chanPeer->id)->value('constructor_name'));

        // Media ref (flags.9) nested shape: paramless constructor instance.
        $media = TlMessageMediaMessageMediaEmpty::query()->sole();
        self::assertSame($message->media, $media->id);

        // Chat namespace sidecar: verbatim id + title + required photo ref.
        $channel = TlChatChannel::query()->sole();
        self::assertSame(self::CHANNEL_ID, $channel->tl_id);
        self::assertSame('Teleframe Café', $channel->title);
        self::assertTrue($channel->verified);
        self::assertTrue($channel->megagroup);
        self::assertSame(
            TlChatPhotoChatPhotoEmpty::query()->sole()->id,
            $channel->photo,
            'channel.photo uuid → chatPhotoEmpty instance',
        );

        // User namespace sidecar: verbatim id.
        self::assertSame(self::USER_ID, TlUserUser::query()->sole()->tl_id);
    }

    public function test_entities_child_rows_preserve_vector_order(): void
    {
        $this->ingestTree();

        $message = TlMessageMessage::query()->sole();
        $rows = $message->entities()->get();

        self::assertCount(3, $rows);
        self::assertSame([0, 1, 2], $rows->pluck('idx')->all(), 'idx keeps wire vector order');

        $bold = TlMessageEntityMessageEntityBold::query()->sole();
        $url = TlMessageEntityMessageEntityUrl::query()->sole();
        $mention = TlMessageEntityMessageEntityMentionName::query()->sole();
        self::assertSame(0, $bold->tl_offset); // reserved word: offset → tl_offset (Naming §4.7)
        self::assertSame(5, $bold->length);
        self::assertSame(21, $url->length);
        self::assertSame(6, $url->tl_offset);
        self::assertSame(33, $mention->tl_offset);
        self::assertSame(self::USER_ID, $mention->user_id);

        self::assertSame([(int) $bold->id, (int) $url->id, (int) $mention->id], $rows->pluck('value_id')->all());
        self::assertSame(3, TlMessageMessageEntities::query()->where('parent_id', $message->id)->count());
    }

    public function test_update_stored_event_fires_with_committed_root_model(): void
    {
        // fake ONLY UpdateStored: a blanket Event::fake() would swallow the
        // Eloquent creating hooks (UUIDv7 PK assignment) too.
        Event::fake([UpdateStored::class]);

        $root = (new UpdateIngestor(events: $this->app['events']))->ingest(self::updateNewMessagePayload(), self::ACCOUNT);

        Event::assertDispatchedTimes(UpdateStored::class, 1);
        Event::assertDispatched(UpdateStored::class, function (UpdateStored $event) use ($root): bool {
            return $event->model instanceof TlUpdateUpdateNewMessage
                && $event->model->is($root)
                && $event->model->wasRecentlyCreated
                && $event->accountId === self::ACCOUNT;
        });
    }

    public function test_tenants_isolate_nested_trees(): void
    {
        $rootA = $this->ingestTree(self::ACCOUNT);

        // Use different Telegram IDs for account 8 to avoid global-ID PK
        // collisions (global types share one row across tenants).
        $ingestor = new UpdateIngestor();
        $otherChannelId = self::CHANNEL_ID + 100;
        $otherUserId = self::USER_ID + 100;
        $ingestor->ingest([
            '_' => 'channel',
            'flags' => (1 << 7) | (1 << 8) | (1 << 13),
            'verified' => true,
            'megagroup' => true,
            'id' => $otherChannelId,
            'access_hash' => -7779317524312221622,
            'title' => 'Other Café',
            'photo' => ['_' => 'chatPhotoEmpty'],
            'date' => 1712345678,
        ], 8);
        $ingestor->ingest([
            '_' => 'user',
            'flags' => (1 << 0) | (1 << 1) | (1 << 2) | (1 << 3),
            'id' => $otherUserId,
            'access_hash' => -5988024083302710253,
            'first_name' => 'Ali',
            'last_name' => 'Alirezaei',
            'username' => 'AliAlirezaei',
        ], 8);
        $rootB = $ingestor->ingest(self::buildUpdateMessage($otherChannelId, $otherUserId), 8);

        self::assertNotSame((string) $rootA->id, (string) $rootB->id, 'separate roots per account');

        // Scoped types (Update, Message) are per-tenant: two rows each.
        self::assertSame(2, TlUpdate::query()->count());
        self::assertSame(2, TlMessage::query()->count());

        // Global-ID types (Chat, User) share one row per Telegram entity.
        self::assertSame(2, TlChat::query()->count(), 'two distinct Telegram channels');
        self::assertSame(2, TlUser::query()->count(), 'two distinct Telegram users');

        // Account 7's view is untouched by the account 8 ingest.
        $messageA = TlMessageMessage::query()->where('id', $rootA->message)->sole();
        self::assertSame(PeerIdTool::channelLong(self::CHANNEL_ID), (int) $messageA->peer_id, 'account 7 peer_id = canonical channel long');

        // Scoped peer rows are per-tenant (different channels per account).
        self::assertSame(self::CHANNEL_ID, TlPeerPeerChannel::query()->where('account_id', self::ACCOUNT)->where('constructor_name', 'peerChannel')->sole()->channel_id);

        // Child rows hang off each tenant's own message instance with
        // disjoint value sets (content aggregation never crosses tenants).
        $rowsA = TlMessageMessageEntities::query()->where('parent_id', $rootA->message)->orderBy('idx')->pluck('value_id')->all();
        $rowsB = TlMessageMessageEntities::query()->where('parent_id', $rootB->message)->orderBy('idx')->pluck('value_id')->all();
        self::assertCount(3, $rowsA);
        self::assertCount(3, $rowsB);
        self::assertSame([], array_intersect($rowsA, $rowsB));
    }

    /**
     * Build an updateNewMessage payload with explicit channel/user IDs.
     */
    private static function buildUpdateMessage(int $channelId, int $userId): array
    {
        return [
            '_' => 'updateNewMessage',
            'message' => [
                '_' => 'message',
                'flags' => (1 << 1) | (1 << 7) | (1 << 8) | (1 << 9),
                'out' => true,
                'id' => 2001,
                'from_id' => ['_' => 'peerUser', 'user_id' => $userId],
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => $channelId],
                'date' => 1724852400,
                'message' => 'Other tenant message',
                'media' => ['_' => 'messageMediaEmpty'],
                'entities' => [
                    ['_' => 'messageEntityBold', 'offset' => 0, 'length' => 5],
                    ['_' => 'messageEntityUrl', 'offset' => 6, 'length' => 21],
                    ['_' => 'messageEntityMentionName', 'offset' => 33, 'length' => 4, 'user_id' => $userId],
                ],
                'flags2' => 0,
            ],
            'pts' => 2001,
            'pts_count' => 1,
        ];
    }

    public function test_full_re_ingest_keeps_counts_stable(): void
    {
        $root = $this->ingestTree();
        $childIds = TlMessageMessageEntities::query()->orderBy('idx')->pluck('id')->all();
        $childValues = TlMessageMessageEntities::query()->orderBy('idx')->pluck('value_id')->all();

        $again = $this->ingestTree();

        self::assertSame((string) $root->id, (string) $again->id, 'root anchor reused');
        self::assertSame((string) $root->message, (string) $again->message, 'message anchor reused');

        $stable = [
            TlUpdate::class => 1,
            TlUpdateUpdateNewMessage::class => 1,
            TlMessage::class => 1,
            TlMessageMessage::class => 1,
            TlMessageMessageEntities::class => 3,
            TlMessageEntityMessageEntityBold::class => 1,
            TlMessageEntityMessageEntityUrl::class => 1,
            TlMessageEntityMessageEntityMentionName::class => 1,
            TlPeer::class => 2,
            TlPeerPeerChannel::class => 2,
            TlPeerPeerUser::class => 1,
            TlMessageMediaMessageMediaEmpty::class => 1,
            TlChat::class => 1,
            TlChatChannel::class => 1,
            TlChatPhotoChatPhotoEmpty::class => 1,
            TlUser::class => 1,
            TlUserUser::class => 1,
        ];
        foreach ($stable as $model => $count) {
            self::assertSame($count, $model::query()->count(), $model . ' row count must stay stable');
        }

        self::assertSame($childIds, TlMessageMessageEntities::query()->orderBy('idx')->pluck('id')->all(), 'child row identities stable');
        self::assertSame($childValues, TlMessageMessageEntities::query()->orderBy('idx')->pluck('value_id')->all(), 'child value links stable');
    }

    public function test_root_without_constructor_fails_loudly(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("'_'");
        (new UpdateIngestor())->ingest(['pts' => 1, 'pts_count' => 1], self::ACCOUNT);
    }
}
