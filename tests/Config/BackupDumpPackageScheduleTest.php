<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use Illuminate\Console\Scheduling\Schedule;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * THE GUARD ON THE PACKAGE-OWNED DUMP TRIGGER (F7, S6, 2026-10-13).
 *
 * THE DEFECT IT PINS. `teleframe:tstore backup` was the only capability in the
 * package with no trigger the package owns. `schedule.publisher` defaults to
 * 'null', so on a default install nothing in src/ ever fired the dump; the
 * documented posture was a hand-written cron line; and meanwhile the no-show
 * watchdog — armed by default — reported that run missing, forever, on exactly
 * the hosts that had followed the shipped instructions. That last part is what
 * makes this more than a missing feature: the package shipped a watchdog whose
 * only evidence of the dump is a dump it could not itself cause.
 *
 * WHAT A HOST RUNS NOW INSTEAD OF CRON. `php artisan schedule:run`, which it
 * already needs for the hourly `teleframe:notify watch --check` this same
 * provider registers. One command, one crontab entry, owned by the package.
 *
 * WHY DEFAULT-OFF IS A FINDING AND NOT COVENANCE. The two possible triggers
 * cannot observe each other: the shipped one-line cron posture is invisible to
 * the Laravel scheduler, and no config key can report it. The package therefore
 * CANNOT prove that defaulting the trigger on cannot double-fire against a
 * crontab a host already has — so the honest move is off, plus a config comment
 * that states the ruling, plus a truth table below that pins the two cases the
 * package can actually decide. A default-on trigger with a hopeful comment is
 * the same defect as the missing trigger, one layer down.
 *
 * EVERY ASSERTION IS BEHAVIOURAL. It asks the operator's question — set the
 * key, then ask the provider what it did — by running the real registration
 * against a real Schedule, never by reading the source.
 */
final class BackupDumpPackageScheduleTest extends TestCase
{
    private const PROVIDER = 'src/Laravel/StoreBackupServiceProvider.php';

    /** The command whose absence was F7. */
    private const DUMP_COMMAND = 'teleframe:tstore backup';

    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    /**
     * GATE 1 — THE PACKAGE OWNS THE TRIGGER. With the key on, the dump is in
     * the scheduler's own event list. This is the test that would have failed
     * before the fix: the registration did not exist, so nothing to find.
     */
    public function test_the_package_registers_the_db_dump_when_the_key_is_on(): void
    {
        $events = $this->registeredDumpEvents(packageSchedule: true);

        self::assertCount(
            1,
            $events,
            'with schedule.package_schedule.enabled=true the package must register exactly one'
            ."\n`".self::DUMP_COMMAND.'` event on Laravel\'s scheduler. Found: '
            .implode(', ', array_column($events, 'command'))."\nThis is F7: the dump is the one"
            ."\ncapability no trigger in the package could fire.",
        );

        self::assertStringContainsString(
            self::DUMP_COMMAND,
            $events[0]['command'],
            'the registered event must BE the dump, not the sibling watchdog check.',
        );
    }

    /**
     * GATE 2 — IT IS A DAILY TRIGGER AT A TIME THE PACKAGE NAMES, NOT A
     * HAND-WRITTEN ONE. The command string is asserted separately (GATE 1);
     * this pins the cadence, because "registered" without a cadence is the
     * half-finished version of the same fix.
     */
    public function test_the_registered_dump_is_daily_at_the_configured_time(): void
    {
        $events = $this->registeredDumpEvents(packageSchedule: true, dailyAt: '04:05');

        self::assertCount(1, $events, 'the dump trigger did not register at all, so nothing to pin.');

        // Laravel renders dailyAt('04:05') as the six-field cron expression
        // 'M H * * * *'. Compared by substring, not by an exact string, because
        // the exact rendering is a framework detail and the property under test
        // is 'fires at 04:05, daily'.
        self::assertStringContainsString(
            ' 4 * *',
            $events[0]['expression'],
            'dailyAt(04:05) must render an expression that fires at hour 4 every day; got "'
            .$events[0]['expression'].'".',
        );

        self::assertStringContainsString(
            '5 ',
            trim($events[0]['expression']),
            'dailyAt(04:05) must render minute 5; got "'.$events[0]['expression'].'".',
        );
    }

    /**
     * GATE 3 — DEFAULT-OFF, WHICH IS THE DOUBLE-FIRE RULING MADE MECHANICAL.
     * On a default install nothing is registered, so the package cannot fire a
     * dump a host's existing crontab already fires. If this ever fails, the
     * package has started guessing about crontabs it cannot see.
     */
    public function test_the_shipped_default_registers_nothing(): void
    {
        $default = $this->app->make('config')->get('teleframe.store.backup.schedule.package_schedule.enabled');

        self::assertFalse(
            $default,
            'schedule.package_schedule.enabled must ship FALSE. A default-on package trigger cannot'
            ."\nprove it will not double-fire against the one-line crontab posture this same config"
            ."\nfile documents, and two database dumps a day into one bucket is a real cost.",
        );

        self::assertSame(
            [],
            $this->registeredDumpEvents(),
            'with the shipped default, no dump event may be registered. Anything here is a package'
            ."\ntrigger a host did not ask for, firing against a crontab it may already have.",
        );
    }

    /**
     * GATE 4 — THE MASTER SWITCH DOES NOT REPURPOSED INTO A DUMP KILLER.
     *
     * `enabled` is documented, and pinned by BackupMasterSwitchTest, as
     * governing the WATCHDOG and explicitly NOT the dump ("WHAT false DOES NOT
     * DO … it does not disable the dump"). If the trigger were gated on it, a
     * host that switched the watchdog off would silently lose its backups —
     * the exact substitution this row refuses.
     */
    public function test_the_watchdog_master_switch_does_not_silence_the_dump_trigger(): void
    {
        $events = $this->registeredDumpEvents(packageSchedule: true, masterEnabled: false);

        self::assertCount(
            1,
            $events,
            'master switch OFF + package trigger ON must still register the dump. `enabled` governs'
            ."\nthe watchdog only; gating the dump on it would turn a documented watchdog switch into a"
            ."\nbackup kill switch, and a host would learn it from a missed backup.",
        );
    }

    /**
     * GATE 5 — A MISSPELLED TIME IS REFUSED BY NAME, NOT SWALLOWED. The
     * registration is wrapped in a catch so a host without the scheduling
     * surface is never a boot failure; if that catch also swallowed a bad
     * time, the trigger would silently not register and the host would believe
     * it had backups.
     */
    public function test_a_misspelled_daily_at_is_refused_by_name(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('package_schedule.daily_at');

        $this->registeredDumpEvents(packageSchedule: true, dailyAt: '4am');
    }

    /**
     * GATE 6 — AN OUT-OF-RANGE TIME IS REFUSED TOO. ctype_digit() alone would
     * accept '99:99'; the range check is what makes GATE 5 a time check rather
     * than a digit check.
     */
    #[DataProvider('unusableTimes')]
    public function test_an_out_of_range_daily_at_is_refused(string $time): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registeredDumpEvents(packageSchedule: true, dailyAt: $time);
    }

    /** @return iterable<string, array{string}> */
    public static function unusableTimes(): iterable
    {
        yield 'hour 99' => ['99:99'];
        yield 'minute 99' => ['03:99'];
        yield 'no separator' => ['0347'];
        yield 'too long' => ['03:470'];
        yield 'words' => ['half past three'];
    }

    /**
     * GATE 7 — THE CONFIG FILE SAYS SO. A comment is what the next reader reads,
     * and this key's ruling (default-off BECAUSE the two triggers cannot see
     * each other) lives nowhere else. The positive claim is pinned too, so the
     * block cannot simply be deleted.
     */
    public function test_the_config_file_states_the_double_fire_ruling(): void
    {
        $comment = ConfigKeyRegistry::commentAbove(
            'store-backup.php',
            "'package_schedule' => [",
        );

        self::assertNotSame('', $comment, 'the package-owned trigger lost its comment block');

        self::assertStringContainsString(
            'DOUBLE-FIRE',
            $comment,
            'the config comment must state the double-fire ruling. It is the reason this key is'
            ."\ndefault-off, and it is not derivable from the code — the code only shows WHICH"
            ."\nkey is read, never WHY cron cannot be detected.",
        );

        self::assertStringContainsString(
            'schedule:run',
            $comment,
            'the config comment must name what the host runs instead of cron. That is the actionable'
            ."\nhalf of the fix, and a host reading only this comment is the audience it is for.",
        );

        self::assertStringContainsString(
            'THE PACKAGE-OWNED TRIGGER',
            $comment,
            'the block must still be headed by what it is. Without this the two claims above could'
            ."\nsurvive in a comment about something else, and the next reader would act on them.",
        );
    }

    /**
     * Run the provider's boot-time dump registration against a real Schedule
     * and report the dump events it registered.
     *
     * @return list<array{command: string, expression: string}>
     */
    private function registeredDumpEvents(
        ?bool $packageSchedule = null,
        ?string $dailyAt = null,
        ?bool $masterEnabled = null,
    ): array {
        $config = $this->app->make('config');

        if ($packageSchedule !== null) {
            $config->set('teleframe.store.backup.schedule.package_schedule.enabled', $packageSchedule);
        }
        if ($dailyAt !== null) {
            $config->set('teleframe.store.backup.schedule.package_schedule.daily_at', $dailyAt);
        }
        if ($masterEnabled !== null) {
            $config->set('teleframe.store.backup.enabled', $masterEnabled);
        }

        $schedule = new Schedule;
        $this->app->instance(Schedule::class, $schedule);

        // The method is private (it is a boot() step, not an API), so it is
        // invoked the way boot() invokes it. Not a mock: this IS the shipping
        // registration, run against a real Schedule.
        (new ReflectionMethod(StoreBackupServiceProvider::class, 'scheduleBackupDump'))
            ->invoke(new StoreBackupServiceProvider($this->app));

        $found = [];
        foreach ($schedule->events() as $event) {
            $command = $event->command ?? '';
            if (! str_contains($command, self::DUMP_COMMAND)) {
                continue;
            }

            $found[] = ['command' => $command, 'expression' => (string) ($event->expression ?? '')];
        }

        return $found;
    }
}
