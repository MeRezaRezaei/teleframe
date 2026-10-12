<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageService;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageViews;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountContext;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * Messages-domain mirror surface (Task 3): the three curated migrations must
 * migrate clean, accept the decomposed write-path row shapes, come back
 * through the Eloquent models, and stay account-scoped.
 */
final class MessageMirrorTest extends TestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'ingest');
        $app['config']->set('database.connections.ingest', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();
        AccountContext::reset();
        $this->migrateMirror();
    }

    public function test_content_message_row_is_retrievable_with_fact_children(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42,
            'id' => 55,
            'peer_type' => 2,
            'peer_id' => 900,
            'constructor' => 'message',
            'date' => 1_700_000_000,
            'message' => 'hello',
            'out' => 1,
            'pinned' => 1,
        ]);
        // FIXTURE CORRECTION (2026-10-03, Task 2.2). Every child row now
        // carries the ENCLOSING MESSAGE's peer, because `tf_messages` and its
        // children are keyed (account_id, peer_type, peer_id, id[, position]) —
        // Telegram numbers messages per chat, so `id` alone is not an identity
        // and a child that dropped half its parent's key re-collapsed the same
        // facts across dialogs. The values written here are the ones the real
        // writer supplies (`MirrorFactWriter` copies them out of the parent
        // row), and the peer columns are NOT NULL with no default precisely so
        // that a fixture which forgot them would fail loudly instead of landing
        // at (account_id, 0, 0, id). Every assertion below is unchanged.
        DB::table('tf_messages_views')->insert(['account_id' => 42, 'peer_type' => 2, 'peer_id' => 900, 'id' => 55, 'views' => 10]);
        DB::table('tf_messages_entities')->insert([
            ['account_id' => 42, 'peer_type' => 2, 'peer_id' => 900, 'id' => 55, 'position' => 0, 'constructor' => 'messageEntityBold', 'offset' => 0, 'length' => 4],
            ['account_id' => 42, 'peer_type' => 2, 'peer_id' => 900, 'id' => 55, 'position' => 1, 'constructor' => 'messageEntityItalic', 'offset' => 4, 'length' => 2],
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(55));

        self::assertNotNull($message);
        self::assertSame('message', $message->constructor);
        self::assertSame(2, $message->peer_type);
        self::assertSame(900, $message->peer_id);
        self::assertTrue($message->out);
        self::assertTrue($message->pinned);
        self::assertSame(10, $message->views->views);
        self::assertCount(2, $message->entities);
        self::assertSame([0, 1], $message->entities->pluck('position')->all());
        self::assertSame('messageEntityItalic', $message->entities[1]->constructor);

        AccountContext::for(42, static function (): void {
            $viewsRow = TfMessageViews::query()->find(55);
            self::assertNotNull($viewsRow);
            self::assertSame(10, $viewsRow->views);
        });
    }

    /**
     * `messageEmpty` still places, and still gets its TYPE DEFAULTS — but it now
     * has to bring its peer, because a message with no peer has no address in a
     * peer-scoped store.
     *
     * FIXTURE CORRECTION + ASSERTION RAISED (2026-10-03, Task 2.2). This test
     * previously inserted a `messageEmpty` with NO peer and asserted
     * `peer_type === 0`. That was not a neutral default assertion — it was the
     * 0/0 corruption itself, written down as the expected outcome: the row
     * landed at (account_id, 0, 0, 56) and nothing said so. `messageEmpty`'s
     * `peer_id` is a conditional flag (`messageEmpty#90a6ca84 flags:# id:int
     * peer_id:flags.0?Peer`), so a peerless one is a REAL wire shape, and the
     * honest handling is the loud one.
     *
     * So the fixture now carries a peer and the assertion is STRONGER: the row
     * must carry ITS OWN peer (not 0), the type defaults still apply to the
     * columns that have them, and the peerless case moves to its own test below
     * where it is asserted to be REFUSED rather than quietly stored.
     */
    public function test_empty_message_places_with_type_defaults(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42,
            'peer_type' => 3,
            'peer_id' => -100,
            'id' => 56,
            'constructor' => 'messageEmpty',
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(56));

        self::assertNotNull($message);
        self::assertSame('messageEmpty', $message->constructor);
        self::assertSame(0, $message->date, 'the type default on `date` still applies');
        self::assertSame('', $message->message, 'the type default on `message` still applies');
        self::assertSame(3, $message->peer_type, 'the peer is the row\'s OWN — 0 here is the corruption this keying change exists to end');
        self::assertSame(-100, $message->peer_id);
    }

    /**
     * A message with NO peer cannot be addressed, so it must be refused LOUDLY
     * rather than stored at (account_id, 0, 0, id).
     *
     * This is the posture the widened key buys, and it is asserted here because
     * the opposite behaviour shipped silently for the whole life of the
     * (account_id, id) key: `peer_type`/`peer_id` carried `->default(0)`, so a
     * write that could not name its peer succeeded and produced a row that
     * belonged to no dialog. The mirror's own key columns now declare NOT NULL
     * with no default (see 2026_09_14_200010_create_tf_messages_tables.php) and
     * `MirrorFactWriter::placeRow()` reports the uncovered column as a clue
     * rather than inventing a key. `tf_dialogs.peer_type` still carries
     * `->default(0)` and
     * `MirrorResolverKeyMatchesShippedDdlTest::test_a_peer_keyed_table_with_a_ddl_default_would_corrupt_silently_not_drop_loudly`
     * pins that contrast on purpose.
     */
    public function test_a_peerless_message_is_refused_rather_than_stored_at_zero_zero(): void
    {
        $refused = false;
        try {
            DB::table('tf_messages')->insert([
                'account_id' => 42,
                'id' => 59,
                'constructor' => 'messageEmpty',
            ]);
        } catch (\Illuminate\Database\QueryException) {
            $refused = true;
        }

        self::assertTrue(
            $refused,
            'a message written without its peer MUST be refused. With a DDL default it would instead '
            .'succeed at (account_id, 0, 0, 59) — a row belonging to no dialog, indistinguishable from a '
            .'real one, with no error anywhere.',
        );
        self::assertSame(
            0,
            AccountContext::for(42, fn () => TfMessage::query()->where('id', 59)->count()),
            'and nothing may be stored for it',
        );
    }

    public function test_service_message_and_action_are_retrievable(): void
    {
        DB::table('tf_messages_service')->insert([
            'account_id' => 42,
            'id' => 77,
            'peer_type' => 2,
            'peer_id' => 900,
            'constructor' => 'messageService',
            'date' => 1_700_000_001,
            'post' => 1,
        ]);
        // FIXTURE CORRECTION (2026-10-03, Task 2.2): the service message's
        // action is a CHILD of that message, so it carries the same peer-scoped
        // key the parent declares. Values unchanged, assertions unchanged.
        DB::table('tf_messages_service_action')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'id' => 77,
            'constructor' => 'messageActionChatCreate',
            'title' => 'group',
        ]);

        $service = AccountContext::for(42, fn () => TfMessageService::query()->find(77));

        self::assertNotNull($service);
        self::assertSame('messageService', $service->constructor);
        self::assertTrue($service->post);
        self::assertNotNull($service->action);
        self::assertSame('messageActionChatCreate', $service->action->constructor);
        self::assertSame('group', $service->action->title);
    }

    public function test_media_union_child_places_partial_rows(): void
    {
        DB::table('tf_messages')->insert([
            'account_id' => 42, 'id' => 58, 'peer_type' => 3, 'peer_id' => -100, 'constructor' => 'message', 'date' => 1, 'message' => 'x',
        ]);
        // FIXTURE CORRECTION (2026-10-03, Task 2.2). This is the one message
        // child whose peer-scope columns needed a `message_` PREFIX: the table
        // already owns `peer_type`/`peer_id` for a peer carried INSIDE its own
        // payload, so the enclosing message's scope is `message_peer_type` /
        // `message_peer_id`. Both are asserted below so the disambiguation
        // cannot rot back into a collision.
        DB::table('tf_messages_media')->insert([
            'account_id' => 42,
            'message_peer_type' => 3,
            'message_peer_id' => -100,
            'id' => 58,
            'constructor' => 'messageMediaContact',
            'phone_number' => '+1800',
        ]);

        $message = AccountContext::for(42, fn () => TfMessage::query()->find(58));

        self::assertNotNull($message->media);
        self::assertSame('messageMediaContact', $message->media->constructor);
        self::assertSame('+1800', $message->media->phone_number);
        self::assertSame(3, $message->media->message_peer_type, 'the media row must carry the enclosing message\'s peer');
        self::assertSame(-100, $message->media->message_peer_id);
    }

    public function test_account_isolation_via_fact_scope(): void
    {
        DB::table('tf_messages')->insert([
            ['account_id' => 1, 'id' => 55, 'peer_type' => 2, 'peer_id' => 900, 'constructor' => 'message', 'date' => 1, 'message' => 'a'],
            ['account_id' => 2, 'id' => 55, 'peer_type' => 2, 'peer_id' => 900, 'constructor' => 'message', 'date' => 1, 'message' => 'b'],
        ]);

        self::assertSame(1, AccountContext::for(1, fn () => TfMessage::query()->count()));
        self::assertSame('a', AccountContext::for(1, fn () => TfMessage::query()->first()->message));
        self::assertSame('b', AccountContext::for(2, fn () => TfMessage::query()->first()->message));
        self::assertSame(2, TfMessage::acrossAccounts()->count());
        self::assertSame(1, TfMessage::forAccount(2)->count());
    }

    private function migrateMirror(): void
    {
        // Direct up() instead of artisan migrate: the provider still registers
        // the purged TeleframeMirrorCommand, so the console kernel cannot boot
        // in this worktree (expected-red until the controller re-wires it).
        foreach ([
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php',
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200011_create_tf_message_entities_tables.php',
            __DIR__.'/../../../src/Laravel/Migrations/2026_09_14_200012_create_tf_message_medias_tables.php',
        ] as $path) {
            $migration = require $path;
            $migration->up();
        }
    }
}
