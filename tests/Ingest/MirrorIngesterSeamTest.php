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

        // A peer the mirror cannot place is an ingest clue — never a silent
        // 0/0 write NOR a silent drop: the row stores with the curated 0/0
        // defaults and the decomposer surfaces the "wrong ingest path" clue
        // (FK-failed → clue signal). inputPeerUser is not a Fact-side ctor.
        $ingester([
            '_' => 'message',
            'id' => 8,
            'peer_id' => ['_' => 'inputPeerUser', 'user_id' => 900], // not a Fact-side ctor
            'date' => 1726000000,
            'message' => 'input variant shape',
            'out' => false,
        ], 42);

        $row = DB::table('tf_messages')->where('account_id', 42)->where('id', 8)->first();
        self::assertNotNull($row, 'the update still stores (curated defaults carry the row)');
        self::assertSame(0, (int) $row->peer_type, 'unresolvable peer → curated 0 default, not a fake placement');
        self::assertSame(0, (int) $row->peer_id, 'unresolvable peer → curated 0 default, not a fake placement');
    }
}
