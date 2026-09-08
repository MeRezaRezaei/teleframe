<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
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
use Symfony\Component\Uid\UuidV7;

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
        self::assertTrue(UuidV7::isValid((string) $anchor->id));
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
        self::assertSame($root->message, (string) $message->id, 'root.message uuid → the message instance');

        // Ref columns carry the child instances' shared PKs (uuids).
        $fromPeer = TlPeerPeerUser::query()->sole();
        self::assertSame(self::USER_ID, $fromPeer->user_id);
        self::assertSame($message->from_id, $fromPeer->id);
        $chanPeer = TlPeerPeerChannel::query()->sole();
        self::assertSame(self::CHANNEL_ID, $chanPeer->channel_id);
        self::assertSame($message->peer_id, $chanPeer->id);
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

        self::assertSame([(string) $bold->id, (string) $url->id, (string) $mention->id], $rows->pluck('value_id')->all());
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
        $rootB = $this->ingestTree(8);

        self::assertNotSame((string) $rootA->id, (string) $rootB->id, 'separate roots per account');

        // Anchor namespaces are per-tenant: two rows, one visible per account.
        self::assertSame(2, TlUpdate::query()->count());
        self::assertSame(2, TlMessage::query()->count());
        self::assertSame(2, TlChat::query()->count());
        self::assertSame(2, TlUser::query()->count());
        self::assertSame(1, TlChat::query()->where('account_id', self::ACCOUNT)->count());
        $chatB = TlChat::query()->where('account_id', 8)->sole();
        self::assertSame('Teleframe Café', TlChatChannel::query()->findOrFail((string) $chatB->id)->title);

        // Account 7's view is untouched by the account 8 ingest.
        $messageA = TlMessageMessage::query()->where('id', $rootA->message)->sole();
        self::assertSame(1, TlPeerPeerChannel::query()->where('id', $messageA->peer_id)->count());

        // Child rows hang off each tenant's own message instance with
        // disjoint value sets (content aggregation never crosses tenants).
        $rowsA = TlMessageMessageEntities::query()->where('parent_id', $rootA->message)->orderBy('idx')->pluck('value_id')->all();
        $rowsB = TlMessageMessageEntities::query()->where('parent_id', $rootB->message)->orderBy('idx')->pluck('value_id')->all();
        self::assertCount(3, $rowsA);
        self::assertCount(3, $rowsB);
        self::assertSame([], array_intersect($rowsA, $rowsB));
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
            TlPeerPeerChannel::class => 1,
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
