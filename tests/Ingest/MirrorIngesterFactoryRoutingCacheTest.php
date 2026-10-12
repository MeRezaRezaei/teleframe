<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\MirrorIngesterFactory;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * MirrorIngesterFactory::build wiring regression — the "Redis two"
 * hot-reload loop must reach the daemon's ingest seam.
 *
 * The provider binds the daemon's MIRROR_INGESTER_KEY seam to
 * MirrorIngesterFactory::build($events, $sends, $routingCache) and the
 * factory threads $routingCache into the UpdateRouter it constructs
 * (line 54). The hot-reload cache is best-effort at the seam: hosts with
 * a working bus redis get real-time listen/ignore control, hosts without
 * one fall back to DB-only routing. This file pins that wiring so a future
 * change cannot silently drop the cache (HANDOFF.md:260 half-wired gap):
 * 1. a refreshed cache answers the routing mode WITHOUT a DB hit (flip the
 *    DB under the running seam — only the cached value can answer);
 * 2. refresh() re-syncs and the reload loop reaches the seam live;
 * 3. a null cache (BC path) still routes from the DB source of truth.
 */
final class MirrorIngesterFactoryRoutingCacheTest extends TestbenchTestCase
{
    /** The tenant every fixture here writes under. */
    private const ACCOUNT = 42;

    /**
     * Plant the tenant these fixtures write under, BEFORE the mirror is migrated.
     *
     * MEASURED: the only account_id literal in this file is 42, used by both the
     * `tg_update_routing` insert and the `$cache->refresh(42)` call.
     *
     * It was unplanted, and `tg_update_routing` carries
     * `account_id → telegram_accounts`, so `refresh()` had nothing to cache and the
     * hot path answered from the DATABASE instead:
     *
     *   the seam answered the routing question from the Redis cache
     *   Failed asserting that an array contains 'tg:routing:rules:42'.
     *
     * The assertion caught a real broken path rather than passing vacuously, which is
     * why the defect surfaced here instead of hiding: the test's whole claim is that
     * the CACHE wins over a flipped DB rule, and with an empty cache it was proving
     * the opposite while looking green.
     *
     * This class extends Orchestra TestbenchTestCase, so it has no
     * `ingestTenantIds()` seam and plants its own. Synthetic values only — no
     * session string, no access hash.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);

        if (! DB::table('telegram_accounts')->where('user_id', self::ACCOUNT)->exists()) {
            DB::table('telegram_accounts')->insert([
                'user_id' => self::ACCOUNT,
                'label' => 'routing-cache-fixture',
                'type' => 'user',
                'dc_id' => 2,
            ]);
        }
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

    private function migrateIngest(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
    }

    /**
     * The seam exactly as MirrorIngesterFactory::build wires it — a real
     * Dispatcher plus a caller-provided routing cache (or null for BC).
     */
    private function seam(?UpdateRoutingCache $routingCache): callable
    {
        return MirrorIngesterFactory::build(
            $this->app->make(Dispatcher::class),
            null,
            $routingCache,
        );
    }

    /**
     * @param  list<Model>  $seen
     */
    private function captureUpdateStored(array &$seen): void
    {
        Event::listen(UpdateStored::class, static function (UpdateStored $event) use (&$seen): void {
            $seen[] = $event->model;
        });
    }

    public function test_provided_cache_is_wired_into_the_built_router_and_wins_over_db(): void
    {
        $this->migrateIngest();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 3,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $redis = new RecordingRedis(new ArrayRedis);
        $cache = new UpdateRoutingCache($redis, DB::connection());
        $cache->refresh(42);

        // Flip the DB source of truth underneath the already-built seam:
        // the hot path must keep answering from the cache (store_only) —
        // a DB consult would read the now-act_on rule and emit.
        DB::table('tg_update_routing')
            ->where('account_id', 42)->where('peer_type', 3)->where('peer_id', 900)
            ->update(['mode' => UpdateRoutingRule::MODE_ACT_ON]);

        /** @var list<Model> $seen */
        $seen = [];
        $this->captureUpdateStored($seen);
        $seam = $this->seam($cache);

        $seam([
            '_' => 'message',
            'id' => 11,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'cache answer wins',
            'out' => false,
        ], 42);

        self::assertCount(0, $seen, 'the threaded cache (store_only) wins — no event; a DB read would have seen act_on');
        self::assertContains(
            UpdateRoutingCache::KEY.':42',
            $redis->hgetKeys,
            'the seam answered the routing question from the Redis cache (hget on the routing hash)'
        );
        self::assertSame(
            1,
            DB::table('tf_messages')->where('account_id', 42)->where('id', 11)->count(),
            'the fact is stored regardless of the routing mode'
        );

        // The reload loop: a DB change → refresh() → the running seam
        // answers act_on on the next update — live control, no restart.
        $cache->refresh(42);
        $seam([
            '_' => 'message',
            'id' => 12,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'reload reached the seam',
            'out' => false,
        ], 42);

        self::assertCount(1, $seen, 'refresh() re-syncs the cache and the seam now acts on the peer');
        self::assertSame('reload reached the seam', (string) $seen[0]->getAttribute('message'));
    }

    public function test_null_cache_bc_path_routes_from_the_db_source_of_truth(): void
    {
        $this->migrateIngest();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 3,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        /** @var list<Model> $seen */
        $seen = [];
        $this->captureUpdateStored($seen);
        $seam = $this->seam(null); // BC — no routing cache wired

        $seam([
            '_' => 'message',
            'id' => 21,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 900],
            'date' => 1726000000,
            'message' => 'db act_on reachable',
            'out' => false,
        ], 42);

        self::assertCount(1, $seen, 'null cache → the router still answers act_on from the DB source of truth');
        self::assertSame('db act_on reachable', (string) $seen[0]->getAttribute('message'));

        // An unmarked peer keeps the store_only default — stored, never an event.
        $seam([
            '_' => 'message',
            'id' => 22,
            'peer_id' => ['_' => 'peerChannel', 'channel_id' => 901],
            'date' => 1726000000,
            'message' => 'unmarked stays silent',
            'out' => false,
        ], 42);

        self::assertCount(1, $seen, 'unmarked peer → store_only (no event) even without a cache');
        self::assertSame(
            2,
            DB::table('tf_messages')->where('account_id', 42)->count(),
            'both facts are mirrored regardless of routing'
        );
    }
}

/**
 * ArrayRedis double that records every hget — the method-call spy proving
 * the seam's routing lookup went through the Redis cache.
 */
final class RecordingRedis implements RedisConnectionContract
{
    /** @var list<string> key of every hget call, in order */
    public array $hgetKeys = [];

    public function __construct(
        private readonly ArrayRedis $inner,
    ) {}

    public function xadd(string $stream, string $id, array $fields, array $options = []): string
    {
        return $this->inner->xadd($stream, $id, $fields, $options);
    }

    public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
    {
        return $this->inner->xpending($stream, $group, $start, $end, $count);
    }

    public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
    {
        return $this->inner->xautoclaim($stream, $group, $consumer, $minIdleMs, $start, $count);
    }

    public function xrange(string $stream, string $start, string $end, int $count = 100): array
    {
        return $this->inner->xrange($stream, $start, $end, $count);
    }

    public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
    {
        return $this->inner->xrevrange($stream, $end, $start, $count);
    }

    public function xdel(string $stream, array $ids): int
    {
        return $this->inner->xdel($stream, $ids);
    }

    public function hdel(string $key, string ...$fields): int
    {
        return $this->inner->hdel($key, ...$fields);
    }

    public function command(string $command, array $arguments = []): mixed
    {
        return $this->inner->command($command, $arguments);
    }

    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
    {
        return $this->inner->xreadgroup($group, $consumer, $streams, $minId);
    }

    public function xack(string $stream, string $group, array $ids): int
    {
        return $this->inner->xack($stream, $group, $ids);
    }

    public function hgetall(string $key): array
    {
        return $this->inner->hgetall($key);
    }

    public function hset(string $key, string $field, string $value): int
    {
        return $this->inner->hset($key, $field, $value);
    }

    public function publish(string $channel, string $payload): int
    {
        return $this->inner->publish($channel, $payload);
    }

    public function subscribe(string $channel, callable $handler): void
    {
        $this->inner->subscribe($channel, $handler);
    }

    public function del(string ...$keys): int
    {
        return $this->inner->del(...$keys);
    }

    public function llen(string $key): int
    {
        return $this->inner->llen($key);
    }

    public function hget(string $key, string $field): ?string
    {
        $this->hgetKeys[] = $key;

        return $this->inner->hget($key, $field);
    }

    public function expire(string $key, int $seconds): bool
    {
        return $this->inner->expire($key, $seconds);
    }
}
