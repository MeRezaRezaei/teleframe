<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use MeRezaRezaei\Teleframe\Laravel\Console\TstoreCommand;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use MeRezaRezaei\Teleframe\Notify\WatchState;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Index\MemoryStoreIndex;
use MeRezaRezaei\Teleframe\Store\Queue\SyncUploadQueue;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Tests\Store\StoreScheduleUpsertDouble;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use Orchestra\Testbench\TestCase;

/**
 * WHERE THE SCHEDULES GET ARMED — the wiring test for the rule that a
 * selectable driver is not an armed schedule.
 *
 * The defect this covers: `teleframe.tstore.schedules` and
 * `teleframe.notify.schedules` were WIRED (ab1961bc) — a host could select
 * 'temporal', a real RecurringScheduleClient was built, and
 * `NullSchedulePublisher` refused to name either key precisely because
 * nothing armed them. Nothing did. `teleframe:tstore schedules` printed
 * describe() and stopped.
 *
 * Four properties are asserted here, and each of them is a way this could
 * be wrong again:
 *
 *   1. NOT AT BOOT. Registering the providers and resolving the seams arms
 *      NOTHING. A provider that armed on every web request or every queue
 *      tick would dial Temporal from a request; that is the surprise the
 *      'null' default exists to prevent.
 *   2. NOT ON EVERY INVOCATION. `teleframe:tstore buckets` — an unrelated
 *      action — arms nothing either. Arming on any command run is a new
 *      failure mode, not a fix.
 *   3. ON AN EXPLICIT ACTION. `schedules --install` reaches install() with
 *      the StoreMaintenanceScheduleSpec payload, and
 *      `schedule --install` reaches the BACKUP seam's install, which until
 *      now had a scheduler and no production caller at all.
 *   4. NOT TWICE, AND NOT BY ACCIDENT. The shipped default is still
 *      'null': arming with it reports 'skipped' and the command says so.
 *      Re-arming refreshes the same ids.
 */
final class TstoreScheduleArmingTest extends TestCase
{
    private StoreScheduleUpsertDouble $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->client = new StoreScheduleUpsertDouble;

        $this->app->make(Kernel::class)->registerCommand(new TstoreCommand);

        // register() only — never boot(). The provider's boot() surface is
        // not what this test is about, and booting it would drag views,
        // observers and migrations in behind a test about two bind keys.
        (new TeleframeServiceProvider($this->app))->register();
        (new NotifyServiceProvider($this->app))->register();

        // The seams the CLI needs that are not about schedules. The arming
        // path must not need credentials or a wire, or it could not be the
        // thing an operator runs by hand.
        $this->app->instance(TstoreCommand::CHANNEL_FACTORY_KEY, static fn (string $bucket): never => throw new \LogicException('the arming path must not build a channel'));
        $this->app->instance(TstoreCommand::INDEX_KEY, new MemoryStoreIndex);
        $this->app->instance(TstoreCommand::HOT_CACHE_KEY, new MemoryHotCache(ttlSeconds: 600, maxBytes: 8 * 1024 * 1024));
        $this->app->instance(TstoreCommand::QUEUE_KEY, new SyncUploadQueue);
        $this->app->instance(BackupSupport::WATCHDOG_KEY, $this->watchdog());
    }

    private function watchdog(): NoShowWatchdog
    {
        return new NoShowWatchdog(new WatchState(new InMemoryCache, 'teleframe.notify.watch.'));
    }

    // ------------------------------------------------------------- 1. not at boot

    public function test_registering_the_providers_and_resolving_the_seams_arms_nothing(): void
    {
        $this->armTemporalDrivers();

        // setUp() already registered both providers; this resolves every
        // schedule seam, with the temporal driver selected, and asserts that
        // having the seam in hand is not the same as arming it.
        $this->app->make(TstoreCommand::SCHEDULES_KEY);
        $this->app->make(NotifyCommand::SCHEDULES_KEY);

        self::assertSame(
            [],
            $this->client->ids(),
            'a provider armed schedules during register()/resolve(). Arming belongs on an '
            .'explicit operator action: a boot-time arm dials Temporal from every web request.',
        );
    }

    public function test_a_plain_command_run_arms_nothing(): void
    {
        $this->armTemporalDrivers();

        self::assertSame(TstoreCommand::SUCCESS, Artisan::call('teleframe:tstore', ['action' => 'schedules']));
        self::assertSame(TstoreCommand::SUCCESS, Artisan::call('teleframe:tstore', ['action' => 'buckets']));

        self::assertSame([], $this->client->ids(), 'a command invocation armed schedules without --install');
    }

    // ------------------------------------------------------- 2. on an explicit action

    public function test_schedules_install_hands_the_maintenance_payload_to_the_seam(): void
    {
        $this->armTemporalDrivers();

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedules', '--install' => true]);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertSame(
            ['tstore-hot-cache-prune', 'tstore-registry-sweep'],
            $this->client->ids(),
            'install() was not reached, or the payload lost entries on the way to the seam.',
        );
        self::assertSame(3600, $this->client->intervals()['tstore-hot-cache-prune']);
        self::assertSame(86400, $this->client->intervals()['tstore-registry-sweep']);
        self::assertStringContainsString('armed', $output);
    }

    public function test_the_cadence_options_reach_the_seam_verbatim(): void
    {
        $this->armTemporalDrivers();

        Artisan::call('teleframe:tstore', [
            'action' => 'schedules',
            '--install' => true,
            '--every-prune' => 120,
            '--every-sweep' => 300,
        ]);

        self::assertSame(120, $this->client->intervals()['tstore-hot-cache-prune']);
        self::assertSame(300, $this->client->intervals()['tstore-registry-sweep']);
    }

    public function test_a_non_positive_cadence_fails_loud_instead_of_arming_an_unthrottled_loop(): void
    {
        $this->armTemporalDrivers();

        $exit = Artisan::call('teleframe:tstore', [
            'action' => 'schedules',
            '--install' => true,
            '--every-prune' => 0,
        ]);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit);
        self::assertStringContainsString('--every-prune', $output);
        self::assertSame([], $this->client->ids(), 'a refused cadence still armed something');
    }

    // ------------------------------------------------------ 3. the backup seam too

    public function test_the_backup_seam_arms_through_its_own_install(): void
    {
        $this->armTemporalDrivers();
        $this->app->instance(BackupSupport::SCHEDULES_KEY, new TemporalSchedulePublisher(
            dsn: '127.0.0.1:7233',
            client: $this->client,
        ));
        $this->app->instance(BackupSupport::SCHEDULER_KEY, new TemporalBackupScheduler(
            $this->app->make(BackupSupport::SCHEDULES_KEY),
            new BackupScheduleSpec(86400),
        ));
        $this->app->instance(BackupSupport::WATCHDOG_KEY, $this->watchdog());

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedule', '--install' => true]);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertSame(['backup-dump', 'backup-watchdog-check'], $this->client->ids(),
            'the backup seam had a scheduler bound and NO production caller of install(); the '
            .'operator had no way to arm the recurring dump.');
        self::assertStringContainsString('backup-dump', $output);
    }

    public function test_arming_the_backup_seam_without_the_scheduler_binding_fails_loud(): void
    {
        $this->armTemporalDrivers();
        $this->app->instance(BackupSupport::WATCHDOG_KEY, $this->watchdog());

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedule', '--install' => true]);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::FAILURE, $exit, 'a missing seam must not exit SUCCESS having armed nothing');
        self::assertStringContainsString(BackupSupport::SCHEDULER_KEY, $output);
    }

    public function test_the_backup_schedule_action_still_only_describes_without_the_flag(): void
    {
        $this->armTemporalDrivers();
        $this->app->instance(BackupSupport::WATCHDOG_KEY, $this->watchdog());

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedule']);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('Schedule driver: null', $output);
        self::assertSame([], $this->client->ids(), 'the describe-only path armed something');
    }

    // ------------------------------------------------------ 4. null stays the default

    public function test_the_shipped_default_stays_null_and_says_skipped_rather_than_pretending(): void
    {
        // No armTemporalDrivers(): the default config, exactly as shipped.
        (new TeleframeServiceProvider($this->app))->register();
        $this->app->forgetInstance(TstoreCommand::SCHEDULES_KEY);

        $exit = Artisan::call('teleframe:tstore', ['action' => 'schedules', '--install' => true]);
        $output = Artisan::output();

        self::assertSame(TstoreCommand::SUCCESS, $exit);
        self::assertStringContainsString('skipped (null schedule driver)', $output);
        self::assertStringContainsString('tstore-hot-cache-prune', $output,
            'a skipped install must still print what WOULD have been armed');
    }

    public function test_arming_twice_refreshes_the_same_ids_and_never_stacks(): void
    {
        $this->armTemporalDrivers();

        Artisan::call('teleframe:tstore', ['action' => 'schedules', '--install' => true]);
        Artisan::call('teleframe:tstore', ['action' => 'schedules', '--install' => true]);

        self::assertSame(
            ['create', 'create', 'update', 'update'],
            $this->client->operations(),
            'a second --install created a schedule instead of refreshing it.',
        );
        self::assertSame(['tstore-hot-cache-prune', 'tstore-registry-sweep'], $this->client->ids());
    }

    // ------------------------------------------------------------------- plumbing

    private function armTemporalDrivers(): void
    {
        $client = $this->client;

        // Whether temporal/sdk and ext-grpc exist is an ENVIRONMENT property
        // (CI has neither), so the resolver is pinned to one explicit outcome
        // rather than left to pick a branch. A test that lets the environment
        // choose passes and fails for reasons unrelated to the code.
        $this->app->instance(
            TemporalClientResolver::class,
            new TemporalClientResolver(null, static fn (string $dsn = '', string $namespace = 'default'): RecurringScheduleClient => $client),
        );
        $this->app['config']->set('teleframe.tstore.schedules', 'temporal');
        $this->app['config']->set('teleframe.notify.schedules', 'temporal');
        $this->app->forgetInstance(TstoreCommand::SCHEDULES_KEY);
        $this->app->forgetInstance(NotifyCommand::SCHEDULES_KEY);
    }
}
