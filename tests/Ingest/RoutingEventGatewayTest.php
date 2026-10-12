<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * RoutingEventGateway — the verbatim's store-the-truth-but-not-an-event
 * boundary: store_only peers persist silently; act_on peers emit UpdateStored.
 */
final class RoutingEventGatewayTest extends TestbenchTestCase
{
    private array $dispatched = [];

    private RoutingEventGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        $router = new UpdateRouter(DB::connection());
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

        $this->gateway = new RoutingEventGateway($router, $events);
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

    private function makeModel(): TlAnchorModelStub
    {
        return new TlAnchorModelStub;
    }

    public function test_act_on_peer_emits_update_stored(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        $model = $this->makeModel();
        $payload = ['peer_id' => ['_type' => 2, '_id' => 900]];

        $dispatched = $this->gateway->emit(42, $payload, $model);

        self::assertTrue($dispatched);
        self::assertCount(1, $this->dispatched);
        self::assertInstanceOf(UpdateStored::class, $this->dispatched[0]);
        self::assertSame(42, $this->dispatched[0]->accountId);
        self::assertSame($model, $this->dispatched[0]->model);
    }

    public function test_unmarked_peer_stores_but_stays_silent(): void
    {
        $model = $this->makeModel();
        $payload = ['peer_id' => ['_type' => 2, '_id' => 777]];

        $dispatched = $this->gateway->emit(42, $payload, $model);

        self::assertFalse($dispatched, 'no rule → store the fact, no event (verbatim default)');
        self::assertCount(0, $this->dispatched, 'nothing marked → nothing emitted; acting is opt-in via settings');
    }

    public function test_store_only_peer_is_persisted_silently(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $model = $this->makeModel();
        $payload = ['peer_id' => ['_type' => 2, '_id' => 900]];

        $dispatched = $this->gateway->emit(42, $payload, $model);

        self::assertFalse($dispatched, 'store_only peer → stored but NOT an event (loop prevention)');
        self::assertCount(0, $this->dispatched, 'no UpdateStored for a store-only channel');
    }
}

/** Minimal TlAnchorModel stand-in for the gateway test. */
final class TlAnchorModelStub extends TlAnchorModel
{
    protected $table = 'tf_messages';

    protected $guarded = [];
}
