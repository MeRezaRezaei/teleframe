<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * UpdateRouter Redis-2-first — the verbatim's real-time listen/ignore
 * control: the hot path answers from the Redis cache (no DB hit), and the
 * cache answer wins over a stale DB until refresh() re-syncs (the observer
 * path, cycle 13 — DB change → refresh → cache updated).
 */
final class UpdateRouterRedisCacheTest extends TestbenchTestCase
{
    private ArrayRedis $redis;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new ArrayRedis;
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

    private function migrateSettings(): void
    {
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_14_000001_create_tg_update_routing_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
    }

    public function test_cache_answer_wins_on_the_hot_path(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $cache = new UpdateRoutingCache($this->redis, DB::connection());
        $router = new UpdateRouter(DB::connection(), $cache);
        $cache->refresh(42);

        // DB says store_only (cached). Flip the DB underneath — the hot
        // path must keep answering from the cache until refresh().
        DB::table('tg_update_routing')
            ->where('account_id', 42)->where('peer_type', 2)->where('peer_id', 900)
            ->update(['mode' => UpdateRoutingRule::MODE_ACT_ON]);
        $this->redis->hset(UpdateRoutingCache::KEY.':42', '2:900', UpdateRoutingRule::MODE_STORE_ONLY);

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->mode(42, 2, 900),
            'hot path answers from the Redis cache (no DB hit)'
        );
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->explicitMode(42, 2, 900),
            'explicitMode also reads the cache first'
        );
    }

    public function test_db_fallback_when_cache_is_empty(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $cache = new UpdateRoutingCache($this->redis, DB::connection());
        $router = new UpdateRouter(DB::connection(), $cache);

        // Cache never refreshed (empty) — the router falls back to the DB.
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->mode(42, 2, 900),
            'cache miss → DB source of truth'
        );
    }

    public function test_router_without_cache_behaves_exactly_as_before(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $router = new UpdateRouter(DB::connection()); // no cache arg — BC

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $router->mode(42, 2, 900),
            'DB-only path unchanged when no cache is provided'
        );
        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $router->mode(42, 1, 1),
            'unknown peer → act_on default'
        );
    }
}
