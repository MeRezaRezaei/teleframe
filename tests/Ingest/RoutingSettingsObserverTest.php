<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\RoutingSettingsChanged;
use MeRezaRezaei\Teleframe\Ingest\RoutingSettingsObserver;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * RoutingSettingsObserver — the verbatim's on-change event loop: a
 * tg_update_routing change refreshes the Redis-2 cache AND dispatches
 * RoutingSettingsChanged to the defined event path.
 */
final class RoutingSettingsObserverTest extends TestbenchTestCase
{
    private ArrayRedis $redis;

    private array $dispatched = [];

    private RoutingSettingsObserver $observer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->redis = new ArrayRedis;
        $events = new class($this->dispatched) implements Dispatcher
        {
            public function __construct(private array &$sink) {}

            public function listen($events, $listener = null): void {}

            public function hasListeners($eventName): bool
            {
                return true;
            }

            public function subscribe($subscriber): void {}

            public function until($event, $payload = [])
            {
                return null;
            }

            public function dispatch($event, $payload = [], $halt = false): array
            {
                $this->sink[] = $event;

                return [];
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}

            public function getRawListeners($eventName): array
            {
                return [];
            }
        };

        $this->observer = new RoutingSettingsObserver(
            new UpdateRoutingCache($this->redis, DB::connection()),
            $events,
        );
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

    public function test_created_syncs_cache_and_emits_event(): void
    {
        $this->migrateSettings();
        $rule = new UpdateRoutingRule([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
            'priority' => 0,
        ]);
        $rule->save(); // real lifecycle: created fires AFTER the row persists

        $this->observer->created($rule);

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $this->redis->hget(UpdateRoutingCache::KEY.':42', '2:900'),
            'cache synced from DB after create'
        );
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(RoutingSettingsChanged::class, $this->dispatched[0]);
        self::assertSame(42, $this->dispatched[0]->accountId());
        self::assertSame(2, $this->dispatched[0]->peerType());
        self::assertSame(900, $this->dispatched[0]->peerId());
        self::assertSame($rule, $this->dispatched[0]->rule, 'the hydrated model snapshot — "data in models to query"');
        self::assertSame('changed', $this->dispatched[0]->change);
    }

    public function test_created_emits_even_without_migrated_table(): void
    {
        // Pre-migration host: observer must still emit — cache refresh
        // fail-closes to empty, the event is the reliable signal.
        $rule = new UpdateRoutingRule([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 901,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $this->observer->created($rule);

        self::assertCount(1, $this->dispatched, 'event fires even before the table exists');
        self::assertNull($this->redis->hget(UpdateRoutingCache::KEY.':42', '2:901'), 'no rows to cache pre-migration');
    }

    public function test_deleted_emits_deleted_change(): void
    {
        $this->migrateSettings();
        $rule = new UpdateRoutingRule([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);
        $rule->exists = true;

        $this->observer->deleted($rule);

        self::assertSame('deleted', $this->dispatched[0]->change);
        self::assertCount(1, $this->dispatched);
    }
}
