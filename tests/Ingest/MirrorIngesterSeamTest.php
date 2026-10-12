<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Ingest\Concerns\RunsMigrations;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * The daemon's default mirror-ingester seam — resolved from the container
 * and driven end to end: one decoded update through decompose → write →
 * classify → gated event against the migrated relational DB. This is the
 * verbatim's regular Redis observer ("observe → relational DB updated"),
 * runnable without any host wiring.
 */
final class MirrorIngesterSeamTest extends TestbenchTestCase
{
    use RunsMigrations;

    /**
     * The tenants this fixture writes under.
     *
     * MEASURED: every account id written here is the literal 42 — the second
     * argument to each `$ingester(...)` call and every `account_id` in the
     * `tg_update_routing` rows. With foreign-key enforcement on, `tf_messages`
     * and `tg_update_routing` both carry `account_id -> telegram_accounts`, so
     * each write was refused at the DATABASE and these tests failed on their
     * own fixture (0 rows where 1 was asserted).
     *
     * Declared HERE rather than by relaxing `seedIngestTenant()`: that helper
     * contracts that each test declares the tenants it means to use, so a test
     * writing under an unplanted id fails LOUDLY. A blanket auto-seed would
     * also rob the orphan-refusal tests of their ability to plant an orphan.
     */
    protected function ingestTenantIds(): array
    {
        return [42];
    }

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function migrateMirror(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);

        // AFTER the migration, never before: `telegram_accounts` is one of the
        // migrated tables, so seeding first would insert into nothing.
        $this->seedIngestTenant(...$this->ingestTenantIds());
    }

    public function test_default_seam_ingests_into_the_mirror(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
        self::assertIsCallable($ingester, 'the default seam is a callable — no host wiring needed');

        $ingester([
            '_' => 'message',
            'id' => 5,
            'peer_id' => ['_type' => 2, '_id' => 900],
            'date' => 1726000000,
            'message' => 'daemon observer proof',
            'out' => false,
        ], 42);

        self::assertSame(1, DB::table('tf_messages')->where('account_id', 42)->where('id', 5)->count());
        self::assertSame(
            'daemon observer proof',
            DB::table('tf_messages')->where('account_id', 42)->where('id', 5)->value('message')
        );
    }

    public function test_default_seam_ignores_unknown_constructors(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

        $ingester(['_' => 'noSuchConstructor', 'id' => 9], 42);

        self::assertSame(0, DB::table('tf_messages')->count(), 'unknown ctor → nothing stored, no throw');
    }

    public function test_hot_path_reads_redis_two_not_the_db(): void
    {
        $this->migrateMirror();

        // The DB says store_only — the emit gate must NOT open for this peer.
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 3,
            'peer_id' => 900,
            'mode' => 'store_only',
        ]);

        // Redis two says act_on — the hot path (daemon, no restart) wins.
        $redis = new ArrayRedis;
        $redis->hset(
            UpdateRoutingCache::KEY.':42',
            '3:900',
            'act_on',
        );
        $this->app->instance(RedisConnectionContract::class, $redis);

        $seen = [];
        Event::listen(UpdateStored::class, static function (UpdateStored $event) use (&$seen): void {
            $seen[] = $event->model;
        });

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
        $ingester([
            '_' => 'message',
            'id' => 11,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'redis two wins',
            'out' => false,
        ], 42);

        self::assertCount(1, $seen, 'Redis-2 act_on overrides the DB store_only — real-time control, no restart');
        self::assertSame(
            'redis two wins',
            (string) $seen[0]->getAttribute('message'),
        );
    }

    public function test_wire_peer_channel_shape_reaches_the_model_and_rows(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

        // The wire decodes the peer as its ctor object (peerChannel#channel_id),
        // not the canonical _type/_id pair — the exact "telegram data came from
        // its mtproto" shape the verbatim says must be insertable as-is.
        $ingester([
            '_' => 'message',
            'id' => 7,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'wire channel shape',
            'out' => false,
        ], 42);

        $row = DB::table('tf_messages')->where('account_id', 42)->where('id', 7)->first();
        self::assertNotNull($row, 'the wire update must store');
        self::assertSame(3, (int) $row->peer_type, 'peerChannel → canonical type 3 (spec enum)');
        self::assertSame(900, (int) $row->peer_id, 'channel_id → canonical peer id');
    }

    public function test_wire_peer_shape_reaches_the_emitted_model(): void
    {
        $this->migrateMirror();

        $seen = [];
        Event::listen(UpdateStored::class, static function (UpdateStored $event) use (&$seen): void {
            $seen[] = $event->model;
        });
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 3,
            'peer_id' => 900,
            'mode' => 'act_on',
        ]);

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);
        $ingester([
            '_' => 'message',
            'id' => 9,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'wire shape to model',
            'out' => false,
        ], 42);

        self::assertCount(1, $seen, 'act_on peer → UpdateStored emitted');
        $model = $seen[0];
        self::assertSame(3, (int) $model->getAttribute('peer_type'), 'model carries canonical peer type');
        self::assertSame(900, (int) $model->getAttribute('peer_id'), 'model carries canonical peer id');
        self::assertSame('wire shape to model', (string) $model->getAttribute('message'));
    }

    public function test_wire_iteration_peer_is_blocked_not_silently_stored(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

        // A peer the mirror cannot place must be BLOCKED — and this test's own
        // name and its own comment always said so. `inputPeerUser` is an
        // Input-side ctor, not a Fact-side one, so `PeerShapeTool::normalize()`
        // cannot resolve it and `fillPeerHalf()` emits the "wrong ingest path"
        // clue instead of inventing a peer.
        //
        // ASSERTION RAISED, 2026-10-03 (Task 2.2). This test previously asserted
        // the OPPOSITE of its own name: it asserted that the row STORED at the
        // curated 0/0 defaults. That is not a neutral default — (account_id, 0, 0,
        // 8) is a message attributed to no dialog at all, and once
        // `tf_messages` is keyed (account_id, peer_type, peer_id, id) — which is
        // what makes message ids mean anything, since Telegram numbers them per
        // chat — a row with no peer has no address. So the behaviour is now the
        // loud one the name promises: the row is NOT stored, and nothing is
        // written at 0/0. `tf_dialogs.peer_type` still carries `->default(0)`,
        // and
        // `MirrorResolverKeyMatchesShippedDdlTest::test_a_peer_keyed_table_with_a_ddl_default_would_corrupt_silently_not_drop_loudly`
        // pins that remaining hazard on purpose.
        $ingester([
            '_' => 'message',
            'id' => 8,
            'peer_id' => ['_' => 'inputPeerUser', 'user_id' => 900], // not a Fact-side ctor
            'date' => 1726000000,
            'message' => 'input variant shape',
            'out' => false,
        ], 42);

        self::assertNull(
            DB::table('tf_messages')->where('account_id', 42)->where('id', 8)->first(),
            'a message whose peer cannot be resolved must NOT be stored — and above all not at (account_id, 0, 0, 8), '
            .'which is a row belonging to no dialog',
        );
        self::assertSame(
            0,
            DB::table('tf_messages')->where('account_id', 42)->where('peer_type', 0)->where('peer_id', 0)->count(),
            'and specifically: no 0/0 row may exist for this account',
        );
    }

    public function test_update_wrapper_unwraps_to_its_inner_entity(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

        // The bus carries full updates (updateNewChannelMessage & friends), and
        // the verbatim says updates are one half of the absolute truth — they
        // must land in the relational DB. The mirror catalog is entity-keyed
        // (message → tf_messages, channel → tf_chats, groupCall →
        // tf_group_calls), so the seam resolves the fact table from the
        // update's INNER entity, exactly like the chunk decomposer does.
        $ingester([
            '_' => 'updateNewChannelMessage',
            'message' => [
                '_' => 'message',
                'id' => 21,
                'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
                'date' => 1726000000,
                'message' => 'wrapper unwrapped',
                'out' => false,
            ],
            'pts' => 101,
            'pts_count' => 1,
        ], 42);

        $row = DB::table('tf_messages')->where('account_id', 42)->where('id', 21)->first();
        self::assertNotNull($row, 'update wrapper → inner message fact stores');
        self::assertSame('wrapper unwrapped', $row->message);
        self::assertSame(3, (int) $row->peer_type, 'peerChannel → canonical peer type 3');
        self::assertSame(900, (int) $row->peer_id, 'channel_id → canonical peer id');
    }

    public function test_signal_update_without_an_entity_stores_nothing(): void
    {
        $this->migrateMirror();

        $ingester = $this->app->make(DaemonCommand::MIRROR_INGESTER_KEY);

        // updateChannelTooLong carries a bare channel_id — a sync signal, no
        // fact payload. The mirror has nothing to place (absent fact → no
        // row), matching the chunk decomposer's ephemeral-wrapper note.
        $ingester([
            '_' => 'updateChannelTooLong',
            'channel_id' => 900,
            'pts' => 101,
        ], 42);

        self::assertSame(0, DB::table('tf_messages')->count(), 'signal-only update → no phantom row');
        self::assertSame(0, DB::table('tf_chats')->count(), 'signal-only update → no phantom chat row');
    }
}
