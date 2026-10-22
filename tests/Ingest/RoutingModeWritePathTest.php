<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Console\Application as ArtisanApplication;
use Illuminate\Events\Dispatcher as RealDispatcher;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\AccountRouteSeeder;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Laravel\Console\RoutingModeCommand;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * The act_on WRITE PATH — proving a host can actually turn dispatch on.
 *
 * R1: nothing in src/ could write MODE_ACT_ON, so UpdateRouter answered
 * store_only for every peer, RoutingEventGateway::emit() returned false and
 * handlers / the Centrifugo feed / TStore were all dead on the normal path.
 * This test drives the REAL command (teleframe:routing) and then asserts the
 * DISPATCH DECISION — emit() returning true and a real Illuminate dispatcher
 * listener actually receiving UpdateStored — not merely that a row exists.
 *
 * R2: AccountRouteSeeder used to updateOrInsert the catch-all (0,0) as
 * store_only, so a re-seed silently reverted an operator's act_on decision.
 * Guarded here.
 *
 * REGISTRATION: the command is bound in the provider's $this->commands([…])
 * list. This test registers it itself (Artisan::starting) so it exercises the
 * command rather than the provider wiring; if the provider later registers it
 * too, the duplicate registration is a harmless no-op.
 */
final class RoutingModeWritePathTest extends TestbenchTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        ArtisanApplication::starting(static function (ArtisanApplication $artisan): void {
            $artisan->resolveCommands([RoutingModeCommand::class]);
        });
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

    private function seeder(): AccountRouteSeeder
    {
        return new AccountRouteSeeder(DB::connection());
    }

    /**
     * A gateway on a REAL Illuminate dispatcher, so the assertion is that a
     * listener genuinely received the event — not that a hand-written fake
     * was called. The app's own dispatcher is avoided because the provider
     * binds Centrifugo / TStore listeners onto UpdateStored.
     *
     * @param  list<UpdateStored>  $sink
     */
    private function gateway(array &$sink): RoutingEventGateway
    {
        $events = new RealDispatcher;
        $events->listen(UpdateStored::class, static function (UpdateStored $event) use (&$sink): void {
            $sink[] = $event;
        });

        return new RoutingEventGateway(new UpdateRouter(DB::connection()), $events);
    }

    private function makeModel(): RoutingModeAnchorModelStub
    {
        return new RoutingModeAnchorModelStub;
    }

    private function catchAllMode(int $accountId): ?string
    {
        $row = DB::table('tg_update_routing')
            ->where('account_id', $accountId)
            ->where('peer_type', UpdateRoutingRule::DEFAULT_PEER_TYPE)
            ->where('peer_id', UpdateRoutingRule::DEFAULT_PEER_ID)
            ->first();

        return $row === null ? null : (string) $row->mode;
    }

    // ---------------------------------------------------------------- R1

    public function test_command_writes_act_on_peer_row(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--peer' => 2,
            '--peer-id' => 900,
        ])->assertExitCode(0);

        $row = DB::table('tg_update_routing')
            ->where('account_id', 42)
            ->where('peer_type', 2)
            ->where('peer_id', 900)
            ->first();

        self::assertNotNull($row, 'the write path must land a rule row for the named peer');
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, (string) $row->mode);
    }

    public function test_act_on_write_makes_the_gateway_dispatch(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--peer' => 2,
            '--peer-id' => 900,
        ])->assertExitCode(0);

        $router = new UpdateRouter(DB::connection());
        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $router->mode(42, 2, 900),
            'the router must resolve the operator-written rule as act_on'
        );

        $sink = [];
        $emitted = $this->gateway($sink)->emit(42, ['peer_id' => ['_type' => 2, '_id' => 900]], $this->makeModel());

        self::assertTrue($emitted, 'emit() returning true IS the dispatch decision R1 is about');
        self::assertCount(1, $sink, 'a real listener must actually receive UpdateStored');
        self::assertSame(42, $sink[0]->accountId);
    }

    public function test_account_wide_act_on_reaches_an_unmarked_peer(): void
    {
        $this->migrateSettings();

        // The real-world report: a host registers onMessage(...) and receives
        // nothing. The catch-all opt-in is the one write that fixes it.
        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--account-wide' => true,
        ])->assertExitCode(0);

        $router = new UpdateRouter(DB::connection());
        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $router->mode(42, 3, 5555),
            'an unmarked peer inherits the account-wide catch-all'
        );

        $sink = [];
        $emitted = $this->gateway($sink)->emit(42, ['channel_id' => 5555], $this->makeModel());

        self::assertTrue($emitted);
        self::assertCount(1, $sink);
    }

    public function test_store_only_still_silences_an_unmarked_peer(): void
    {
        $this->migrateSettings();

        // The loop-prevention invariant must survive the new write path: an
        // account explicitly flipped to store_only dispatches nothing.
        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'store_only',
            '--account-wide' => true,
        ])->assertExitCode(0);

        $sink = [];
        $emitted = $this->gateway($sink)->emit(42, ['peer_id' => ['_type' => 2, '_id' => 900]], $this->makeModel());

        self::assertFalse($emitted, 'store_only is still the silent path');
        self::assertCount(0, $sink);
    }

    // ---------------------------------------------------------------- R2

    public function test_reseed_never_reverts_an_explicit_account_wide_act_on(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--account-wide' => true,
        ])->assertExitCode(0);

        $this->seeder()->seed(42);
        $this->seeder()->seed(42);

        self::assertSame(
            UpdateRoutingRule::MODE_ACT_ON,
            $this->catchAllMode(42),
            'the seeder must not overwrite an explicit operator decision'
        );
    }

    public function test_reseed_still_seeds_a_fresh_account_as_store_only(): void
    {
        $this->migrateSettings();

        $this->seeder()->seed(77);

        self::assertSame(
            UpdateRoutingRule::MODE_STORE_ONLY,
            $this->catchAllMode(77),
            'with nothing explicit, the seeder behaviour is unchanged'
        );
    }

    public function test_reseed_never_reverts_an_explicit_per_peer_act_on(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--peer' => 2,
            '--peer-id' => 900,
        ])->assertExitCode(0);

        $this->seeder()->seed(42);

        $row = DB::table('tg_update_routing')
            ->where('account_id', 42)
            ->where('peer_type', 2)
            ->where('peer_id', 900)
            ->first();

        self::assertNotNull($row);
        self::assertSame(UpdateRoutingRule::MODE_ACT_ON, (string) $row->mode);

        $sink = [];
        $emitted = $this->gateway($sink)->emit(42, ['peer_id' => ['_type' => 2, '_id' => 900]], $this->makeModel());

        self::assertTrue($emitted, 'the explicit per-peer rule still outranks the seeded catch-all');
        self::assertCount(1, $sink);
    }

    // ---------------------------------------------------------------- R1d

    public function test_command_refuses_without_an_explicit_scope(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', ['account' => 42])
            ->assertExitCode(1);

        self::assertNull(
            $this->catchAllMode(42),
            'an unconfirmed run must not write the account-wide catch-all'
        );
    }

    public function test_command_refuses_a_half_given_peer_scope(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', ['account' => 42, '--peer' => 2])
            ->assertExitCode(1);

        $this->artisan('teleframe:routing', ['account' => 42, '--peer-id' => 900])
            ->assertExitCode(1);

        self::assertSame(0, DB::table('tg_update_routing')->count());
    }

    public function test_command_refuses_an_unknown_mode(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'listen_harder',
            '--peer' => 2,
            '--peer-id' => 900,
        ])->assertExitCode(1);

        self::assertSame(0, DB::table('tg_update_routing')->count());
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->migrateSettings();

        $this->artisan('teleframe:routing', [
            'account' => 42,
            '--mode' => 'act_on',
            '--peer' => 2,
            '--peer-id' => 900,
            '--dry-run' => true,
        ])->assertExitCode(0);

        self::assertSame(0, DB::table('tg_update_routing')->count());
    }
}

/** Minimal TlAnchorModel stand-in for the gateway dispatch assertion. */
final class RoutingModeAnchorModelStub extends TlAnchorModel
{
    protected $table = 'tf_messages';

    protected $guarded = [];
}
