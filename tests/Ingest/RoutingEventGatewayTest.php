<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Contracts\Events\Dispatcher as DispatcherContract;
use Illuminate\Database\DatabaseTransactionsManager;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use RuntimeException;

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
        $events = new class($this->dispatched) implements DispatcherContract
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

    // =====================================================================
    // THE OWED DISPATCH-OUTCOME SURFACE
    //
    // Every test below uses a REAL Illuminate\Events\Dispatcher over the
    // real :memory: sqlite connection, because the whole point is which
    // framework path `dispatch()` takes. A fake dispatcher would prove
    // nothing about deferral.
    // =====================================================================

    /** @param  list<string>  $log  */
    private function realGateway(array &$log, ?DatabaseTransactionsManager $manager = null): RoutingEventGateway
    {
        $events = new Dispatcher($this->app);
        $events->setTransactionManagerResolver(
            $manager === null
                ? static fn (): null => null
                : static fn (): DatabaseTransactionsManager => $manager
        );
        $events->listen(UpdateStored::class, static function (UpdateStored $e) use (&$log): void {
            $log[] = 'listener ran for account '.$e->accountId;
        });

        return new RoutingEventGateway(new UpdateRouter(DB::connection()), $events);
    }

    private function transactionManager(): DatabaseTransactionsManager
    {
        $manager = new DatabaseTransactionsManager;
        DB::connection()->setTransactionManager($manager);

        return $manager;
    }

    private function actOn(): array
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_ACT_ON,
        ]);

        return ['peer_id' => ['_type' => 2, '_id' => 900]];
    }

    public function test_outcome_inside_open_transaction_is_scheduled_and_listeners_have_not_run(): void
    {
        $this->transactionManager();
        $log = [];
        $gateway = $this->realGateway($log, $this->transactionManager());
        $payload = $this->actOn();

        DB::beginTransaction();

        try {
            $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

            self::assertSame(RoutingEventGateway::OUTCOME_SCHEDULED, $outcome['outcome']);
            self::assertTrue($outcome['routed'], 'act_on was routed');
            self::assertTrue($outcome['dispatched'], 'the event object WAS handed to the dispatcher');
            self::assertFalse($outcome['listeners_ran'], 'the queue grew → the listeners have NOT run');
            self::assertTrue($outcome['scheduled']);
            self::assertSame([], $log, 'nothing may have run yet');
        } finally {
            DB::rollBack();
        }

        self::assertSame([], $log, 'a rolled-back transaction drops the callback; the listener never runs');
    }

    public function test_scheduled_dispatch_really_does_run_at_commit(): void
    {
        $log = [];
        $gateway = $this->realGateway($log, $this->transactionManager());
        $payload = $this->actOn();

        DB::beginTransaction();
        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_SCHEDULED, $outcome['outcome']);
        self::assertSame([], $log, 'precondition: the listener has not run inside the transaction');

        DB::commit();

        self::assertSame(['listener ran for account 42'], $log, 'the scheduled listener runs at commit');
    }

    public function test_outcome_with_no_open_transaction_is_listeners_ran(): void
    {
        $log = [];
        $gateway = $this->realGateway($log, $this->transactionManager());
        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        // THE CONTROL THAT PROVES THE TRI-STATE IS NOT GUESSED. `dispatch()`
        // returns NULL on this path too — addCallback() finds no applicable
        // transaction and invokes the listener on the spot — so a
        // null-means-deferred reading would answer `scheduled` here and be
        // wrong. The callback queue is what separates them.
        self::assertSame(RoutingEventGateway::OUTCOME_LISTENERS_RAN, $outcome['outcome']);
        self::assertTrue($outcome['listeners_ran']);
        self::assertFalse($outcome['scheduled']);
        self::assertSame(['listener ran for account 42'], $log, 'ran inline, inside the call');
    }

    public function test_outcome_without_a_transaction_manager_is_listeners_ran(): void
    {
        $log = [];
        $gateway = $this->realGateway($log); // resolver returns null → no manager
        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_LISTENERS_RAN, $outcome['outcome']);
        self::assertSame(['listener ran for account 42'], $log);
    }

    public function test_outcome_for_store_only_is_not_routed(): void
    {
        $this->migrateSettings();
        DB::table('tg_update_routing')->insert([
            'account_id' => 42,
            'peer_type' => 2,
            'peer_id' => 900,
            'mode' => UpdateRoutingRule::MODE_STORE_ONLY,
        ]);

        $log = [];
        $gateway = $this->realGateway($log, $this->transactionManager());

        $outcome = $gateway->emitOutcome(42, ['peer_id' => ['_type' => 2, '_id' => 900]], $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_NOT_ROUTED, $outcome['outcome']);
        self::assertFalse($outcome['routed']);
        self::assertFalse($outcome['dispatched'], 'no event object was even built');
        self::assertFalse($outcome['listeners_ran']);
        self::assertFalse($outcome['scheduled']);
        self::assertSame([], $log);
    }

    public function test_outcome_without_a_bound_dispatcher_is_no_dispatcher_not_a_silent_success(): void
    {
        $log = [];
        $gateway = new RoutingEventGateway(new UpdateRouter(DB::connection()), null);
        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        // The fact the old docblock could only STATE is now readable.
        self::assertSame(RoutingEventGateway::OUTCOME_NO_DISPATCHER, $outcome['outcome']);
        self::assertTrue($outcome['routed'], 'the routing decision WAS act_on');
        self::assertFalse($outcome['dispatched'], '…and no handoff happened at all');
        self::assertFalse($outcome['listeners_ran']);
        self::assertFalse($outcome['scheduled']);
        self::assertSame([], $log);

        // The bool contract is unchanged: still true, exactly as before.
        self::assertTrue($gateway->emit(42, $payload, $this->makeModel()));
    }

    public function test_emit_bool_is_unchanged_by_the_new_surface(): void
    {
        $log = [];
        $gateway = $this->realGateway($log, $this->transactionManager());
        $payload = $this->actOn();

        DB::beginTransaction();

        try {
            // The single most important compatibility fact: `emit()` is
            // exactly `routed`, so the deferred outcome does NOT flip it.
            self::assertTrue($gateway->emit(42, $payload, $this->makeModel()));
            self::assertSame([], $log, 'still inside the transaction: nothing has run');
        } finally {
            DB::commit();
        }

        self::assertSame(['listener ran for account 42'], $log, 'the listener did run at commit');
    }

    public function test_outcome_when_dispatch_throws_is_dispatch_failed(): void
    {
        $gateway = new RoutingEventGateway(new UpdateRouter(DB::connection()), new class implements DispatcherContract
        {
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
                throw new RuntimeException('wiring is wrong');
            }

            public function push($event, $payload = []): void {}

            public function flush($event): void {}

            public function forget($event): void {}

            public function forgetPushed(): void {}
        });

        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_DISPATCH_FAILED, $outcome['outcome']);
        self::assertTrue($outcome['routed'], 'routing still happened; only the handoff died');
        self::assertFalse($outcome['dispatched']);
        self::assertFalse($outcome['listeners_ran']);
        self::assertFalse($outcome['scheduled']);
    }

    public function test_third_party_dispatcher_returning_array_is_listeners_ran(): void
    {
        $seen = [];
        $gateway = new RoutingEventGateway(
            new UpdateRouter(DB::connection()),
            new class($seen) implements DispatcherContract
            {
                /** @param  list<object>  $seen */
                public function __construct(private array &$seen) {}

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
                    $this->seen[] = $event;

                    return [];
                }

                public function push($event, $payload = []): void {}

                public function flush($event): void {}

                public function forget($event): void {}

                public function forgetPushed(): void {}
            }
        );

        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        // An array return is self-describing, so a non-Illuminate
        // dispatcher needs no reflection at all.
        self::assertSame(RoutingEventGateway::OUTCOME_LISTENERS_RAN, $outcome['outcome']);
        self::assertTrue($outcome['listeners_ran']);
        self::assertCount(1, $seen);
    }

    public function test_a_dispatcher_whose_queue_cannot_be_read_reports_unknown_never_a_guess(): void
    {
        $events = new Dispatcher($this->app);
        // A manager the framework does not have: addCallback() is callable and
        // runs the listener inline, but there is no callback queue to count,
        // and `dispatch()` returns null on that path. The honest answer is
        // `unknown` — NOT `listeners_ran`, which would be an invention.
        $events->setTransactionManagerResolver(static fn (): object => new class
        {
            /** @param  callable(): void  $callback */
            public function addCallback(callable $callback): void
            {
                $callback();
            }
        });
        $events->listen(UpdateStored::class, static function (): void {});

        $gateway = new RoutingEventGateway(new UpdateRouter(DB::connection()), $events);
        $payload = $this->actOn();

        $outcome = $gateway->emitOutcome(42, $payload, $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_UNKNOWN, $outcome['outcome']);
        self::assertTrue($outcome['dispatched'], 'the handoff DID happen — only the outcome is unreadable');
        self::assertFalse($outcome['listeners_ran'], 'unknown must not claim the listeners ran');
        self::assertFalse($outcome['scheduled'], 'unknown must not claim they are waiting either');
    }

    public function test_listeners_ran_never_claims_a_listener_succeeded(): void
    {
        $controlLog = [];
        $gateway = $this->realGateway($controlLog, $this->transactionManager());

        // A listener that throws is swallowed by the gateway. The outcome
        // describes the DISPATCH, never a listener's success — assert the
        // boundary by proving a throwing listener does not change it.
        $throwing = new Dispatcher($this->app);
        $throwing->setTransactionManagerResolver(static fn (): null => null);
        $throwing->listen(UpdateStored::class, static function (): void {
            throw new RuntimeException('listener exploded');
        });

        $gatewayWithThrowingListener = new RoutingEventGateway(new UpdateRouter(DB::connection()), $throwing);
        $payload = $this->actOn();

        $outcome = $gatewayWithThrowingListener->emitOutcome(42, $payload, $this->makeModel());

        self::assertSame(RoutingEventGateway::OUTCOME_DISPATCH_FAILED, $outcome['outcome'], 'the throw surfaced as a failed dispatch, not as a success');
        self::assertSame(
            RoutingEventGateway::OUTCOME_LISTENERS_RAN,
            $gateway->emitOutcome(42, $payload, $this->makeModel())['outcome'],
            'the control gateway with no listener still reports listeners_ran',
        );
    }
}

/** Minimal TlAnchorModel stand-in for the gateway test. */
final class TlAnchorModelStub extends TlAnchorModel
{
    protected $table = 'tf_messages';

    protected $guarded = [];
}
