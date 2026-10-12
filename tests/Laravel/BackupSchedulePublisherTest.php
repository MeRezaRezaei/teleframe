<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests;

use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\TemporalBackupScheduler;
use MeRezaRezaei\Teleframe\Store\Schedules\NullSchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\RecurringScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\SchedulePublisher;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalClientResolver;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalScheduleClient;
use MeRezaRezaei\Teleframe\Store\Schedules\TemporalSchedulePublisher;
use Orchestra\Testbench\TestCase;
use RuntimeException;

/**
 * Cycle 19 audit: the backup schedule driver could not work under ANY
 * configuration, and the goal claiming otherwise was marked `[x]`.
 *
 * What shipped: the config key was read, `temporal` built
 * `new TemporalSchedulePublisher(dsn:…, taskQueue:…)` with NO CLIENT, the
 * publisher never builds one either, and the only TemporalScheduleClient
 * implementor in the tree was an anonymous class in a test. So `temporal` had
 * exactly one reachable outcome — the no-client throw — and `null` reported
 * 'skipped'. The recurring database dump NEVER FIRED ITSELF, which is the
 * entire sentence the goal exists to satisfy ("make a backup from it in
 * schedules", 2026-09-24).
 *
 * What these tests pin, so it cannot silently regress into a green lie:
 *   1. 'temporal' does not silently report success — it refuses, at CONFIG
 *      time, naming the class the host must bind;
 *   2. 'null' (the shipped default) reports 'skipped' HONESTLY;
 *   3. with a host-bound RECURRING client, interval_seconds arms a real
 *      recurring schedule.
 */
final class BackupSchedulePublisherTest extends TestCase
{
    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    private function resolve(): SchedulePublisher
    {
        return $this->app->make(BackupSupport::SCHEDULES_KEY);
    }

    public function test_the_default_driver_still_produces_the_null_publisher(): void
    {
        // The shipped default must not change behaviour for an existing host.
        self::assertInstanceOf(NullSchedulePublisher::class, $this->resolve());
    }

    public function test_an_unconfigured_driver_reports_skipped_honestly(): void
    {
        // 'skipped' is only honest because the host was never told a schedule
        // was armed. It must name the driver, so nobody reads it as "backed up".
        $status = (new TemporalBackupScheduler($this->resolve(), new BackupScheduleSpec(86400)))->install();

        self::assertSame('skipped (null schedule driver)', $status['backup-dump']);
        self::assertSame('skipped (null schedule driver)', $status['backup-watchdog-check']);
        self::assertStringContainsString('Schedule driver: null', $this->resolve()->describe());
    }

    public function test_the_temporal_driver_arms_a_recurring_client_with_n_o_host_written_code(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        // Cycle 21. This test USED to assert a refusal, and it was green only
        // because the package had no client at all. The seam now supplies a
        // client with ZERO host-written PHP — the whole point of the goal.
        //
        // The resolver is bound EXPLICITLY on purpose. Whether temporal/sdk and
        // ext-grpc are installed is a property of the ENVIRONMENT (require-dev
        // here; the CI image has no ext-grpc), and a test that lets the
        // environment pick its branch passes and fails for reasons that have
        // nothing to do with the code. Pipeline #203 failed exactly that way.
        $publisher = $this->resolve();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertTrue(
            $publisher->armsRecurring(),
            'a cadence must be armable, not merely startable — a one-shot client would silently drop interval_seconds',
        );
    }

    public function test_the_temporal_driver_refuses_by_name_when_the_sdk_is_unavailable(): void
    {
        // The OTHER arm, pinned where it is actually decided. A factory that
        // models "temporal/sdk is not installed here" must produce a refusal
        // naming the package — never NullSchedulePublisher, because a silently
        // disabled backup schedule while the host believes it is armed is the
        // worst available failure.
        $this->bindResolverBuilding(null);
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        try {
            $publisher = $this->resolve();
            self::fail('expected a loud refusal, got '.get_debug_type($publisher));
        } catch (RuntimeException $e) {
            self::assertStringContainsString('temporal/sdk', $e->getMessage());
        }
    }

    public function test_the_temporal_driver_never_degrades_to_the_null_publisher(): void
    {
        $this->bindResolverBuilding($this->fakeRecurringClient());
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        // The failure this pins is the worst one available for a backup: a host
        // that believes its dump is armed while the publisher reports 'skipped'.
        // Whatever the environment, the `temporal` driver answers with a real
        // publisher or a named refusal — never a silent null.
        $publisher = $this->resolve();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertNotInstanceOf(NullSchedulePublisher::class, $publisher);

        // THE HONESTY PIN, and the reason this test existed. It used to assert
        // only `assertStringNotContainsString('skipped', …)`, which passed
        // trivially: the OLD describe() opened with "tstore maintenance
        // schedules are enabled" — a string that never contained the word
        // 'skipped', so the assertion could not fail for the reason it named.
        // 'enabled' is the claim that is actually false, and 'Nothing is armed'
        // is the truth that replaces it, so those are what is pinned now.
        $describe = $publisher->describe();

        self::assertStringNotContainsString('enabled', strtolower($describe));
        self::assertStringContainsString('Nothing is armed', $describe);
    }

    /**
     * Pin the resolver the provider will use, with an explicit SDK outcome.
     * null models "temporal/sdk is not installed in this environment".
     */
    private function bindResolverBuilding(?RecurringScheduleClient $client): void
    {
        $this->app->instance(
            TemporalClientResolver::class,
            new TemporalClientResolver(null, static fn (): ?RecurringScheduleClient => $client),
        );
    }

    public function test_a_host_bound_recurring_client_still_wins_over_the_packaged_sdk_client(): void
    {
        // Backward compatibility is the point: a host that binds its own client
        // must keep it even though the package can now build one itself, or
        // every third-party implementor would be silently swapped out.
        $client = $this->fakeRecurringClient();
        $this->app->instance(TemporalScheduleClient::class, $client);
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app['config']->set('teleframe.store.backup.schedule.interval_seconds', 3600);
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        $status = (new TemporalBackupScheduler($this->resolve(), new BackupScheduleSpec(3600)))->install();

        self::assertNotEmpty($client->armed, 'the HOST client must be the one used');
        self::assertSame('backup-dump', $client->armed[0]['scheduleId']);
        self::assertStringContainsString('recurring every 3600s', $status['backup-dump']);
    }

    public function test_a_host_bound_client_is_injected_and_the_publisher_is_built_from_config(): void
    {
        $client = $this->fakeRecurringClient();
        $this->app->instance(TemporalScheduleClient::class, $client);
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app['config']->set('teleframe.store.backup.schedule.dsn', '10.0.0.5:7233');
        $this->app['config']->set('teleframe.store.backup.schedule.task_queue', 'backups');
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        $publisher = $this->resolve();

        self::assertInstanceOf(TemporalSchedulePublisher::class, $publisher);
        self::assertSame(
            '10.0.0.5:7233',
            $publisher->dsn(),
            'the configured DSN must be the one the publisher uses',
        );
    }

    public function test_with_a_host_bound_client_the_daily_dump_actually_recurs(): void
    {
        $client = $this->fakeRecurringClient();
        $this->app->instance(TemporalScheduleClient::class, $client);
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'temporal');
        $this->app['config']->set('teleframe.store.backup.schedule.interval_seconds', 43200);
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        $status = (new TemporalBackupScheduler($this->resolve(), new BackupScheduleSpec(43200)))->install();

        // This is criterion (1)+(2) of the false goal, in a form that is true:
        // a registered RECURRING schedule id, on the daily cadence, naming the
        // real activity — not 'skipped' and not a one-shot startWorkflow.
        self::assertStringContainsString('recurring every 43200s', $status['backup-dump']);
        self::assertStringContainsString('sched-backup-dump', $status['backup-dump']);
        self::assertSame('teleframe:tstore backup', $client->armed[0]['activity']);
        self::assertSame(43200, $client->armed[0]['interval']);
    }

    public function test_an_unknown_driver_fails_loudly_instead_of_disabling_the_backup(): void
    {
        $this->app['config']->set('teleframe.store.backup.schedule.publisher', 'carrier-pigeon');
        $this->app->forgetInstance(BackupSupport::SCHEDULES_KEY);

        // Silently falling back to 'null' would leave the host believing its
        // backup schedule is armed while nothing fires. For a backup, a loud
        // refusal is the only safe failure.
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('expected null|temporal');

        $this->resolve();
    }

    public function test_binding_the_publisher_never_dials_temporal(): void
    {
        // The binding is deferred and nothing dials: the standalone-smoke gate
        // constructs every module with no container booted, and a boot-time dial
        // would open a queue connection during a Laravel boot.
        $publisher = new TemporalSchedulePublisher('127.0.0.1:7233');

        self::assertSame('127.0.0.1:7233', $publisher->dsn());
    }

    private function fakeRecurringClient(): RecurringScheduleClient
    {
        return new class implements RecurringScheduleClient
        {
            /** @var list<array{scheduleId: string, activity: string, interval: int, queue: string}> */
            public array $armed = [];

            public function createOrUpdateSchedule(
                string $scheduleId,
                string $activity,
                int $intervalSeconds,
                string $taskQueue,
                array $args = [],
            ): string {
                $this->armed[] = [
                    'scheduleId' => $scheduleId,
                    'activity' => $activity,
                    'interval' => $intervalSeconds,
                    'queue' => $taskQueue,
                ];

                return 'sched-'.$scheduleId;
            }

            public function startWorkflow(string $workflow, string $taskQueue, array $args): string
            {
                return 'wf-should-not-be-used';
            }
        };
    }
}
