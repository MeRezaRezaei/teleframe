<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * UpdateRoutingCache — the verbatim's "Redis two" hot-reload settings layer.
 *
 * DB is source of truth; refresh() mirrors rules into Redis and publishes
 * the reload channel; mode() answers from the cache (no DB hit per update).
 */
final class UpdateRoutingCacheTest extends TestbenchTestCase
{
    private ArrayRedis $redis;

    private UpdateRoutingCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new ArrayRedis;
        $this->cache = new UpdateRoutingCache($this->redis, DB::connection());
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

    public function test_refresh_mirrors_db_rules_into_redis(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            ['account_id' => 42, 'peer_type' => 2, 'peer_id' => 900, 'mode' => UpdateRoutingRule::MODE_STORE_ONLY],
            ['account_id' => 42, 'peer_type' => 2, 'peer_id' => 901, 'mode' => UpdateRoutingRule::MODE_ACT_ON],
        ]);

        $count = $this->cache->refresh(42);

        self::assertSame(2, $count);
        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->cache->mode(42, 2, 900),
            'store-only rule readable from the cache'
        );
        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $this->cache->mode(42, 2, 901),
            'act-on rule readable from the cache'
        );
        self::assertNull($this->cache->mode(42, 3, 999), 'uncached peer → null');
        self::assertNull($this->cache->mode(99, 2, 900), 'different account → not visible');
    }

    public function test_refresh_publishes_reload_channel(): void
    {
        $published = null;
        $this->redis->subscribe('tg:bus:reload', function (string $channel, string $payload) use (&$published): void {
            $published = json_decode($payload, true);
        });

        $this->cache->refresh(7);

        self::assertSame('routing', $published['kind'], 'reload event announces routing refresh');
        self::assertSame(7, $published['account_id']);
    }

    public function test_refresh_replaces_stale_state(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            ['account_id' => 42, 'peer_type' => 2, 'peer_id' => 900, 'mode' => UpdateRoutingRule::MODE_ACT_ON],
        ]);
        $this->cache->refresh(42);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $this->cache->mode(42, 2, 900));

        // The settings change in the DB (observer writes the new rule)…
        DB::table('tg_update_routing')->where('account_id', 42)->update(['mode' => UpdateRoutingRule::MODE_STORE_ONLY]);
        // …cached value is stale until refresh…
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, $this->cache->mode(42, 2, 900));
        // …refresh re-syncs and the daemon now ignores-only stores.
        $this->cache->refresh(42);
        self::assertSame(UpdateRoutingRule::MODE_STORE_ONLY, $this->cache->mode(42, 2, 900));
    }
}
