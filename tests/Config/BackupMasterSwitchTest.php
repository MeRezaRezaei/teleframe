<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use Illuminate\Console\Scheduling\Schedule;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

/**
 * The guard for teleframe.store.backup.enabled — INVERTED from what it was.
 *
 * This file used to be BackupMasterSwitchInertTest, and it asserted the
 * opposite of everything below: that the master switch was still absorbed, and
 * that the config file still warned about it. That was correct then. The defect
 * it pinned was real, and its own docblock said what happens when somebody
 * fixes it — "this fails and makes them correct the config comment in the same
 * change". So the guard is inverted here rather than deleted: a wiring change
 * with no test that can fail is the same defect class as the dead key.
 *
 * WHAT THE BUG WAS. The key was read at two sites and both folded it into
 *
 *     enabled   = master || schedule.enabled
 *     scheduled = schedule.enabled
 *     if (! enabled || ! scheduled) { return; }
 *
 * and `!(A||B) || !B` reduces to `!B`, so the master term was absorbed by the
 * second disjunct. With the shipped schedule.enabled=true,
 * TELEFRAME_STORE_BACKUP_ENABLED=false skipped nothing: the watchdog still
 * heartbeats and `teleframe:notify watch --check` is still registered hourly.
 * This is the class grep cannot see — the key IS read, so grep calls it healthy.
 *
 * THE FIX. One reader (masterEnabled()) returning the key's own value, and
 * every call site gating on it with AND.
 *
 * WHAT CAME AFTER (F13-1, 2026-10-16). The table below gained a third switch,
 * `triggerArmed()`, because the watchdog's expectation is gated on something
 * neither of these two answers: is there anything to BE LATE for? On the
 * shipped defaults there was not — the package's own dump trigger is
 * default-off and a crontab is invisible to the package — so a default install
 * armed an alarm for a heartbeat nothing here can produce, and every host on
 * the shipped posture was told "backup missing" forever. The master switch was
 * never wrong about its own key; it was answering a different question.
 *
 * WHY EVERY ASSERTION HERE IS BEHAVIOURAL. The first version of this file
 * guarded the source with a lexical rule ("no line may contain both
 * masterEnabled() and ||"), and it fired on the FIXED code:
 * `if (! masterEnabled($config) || ! $scheduled)` is De Morgan on two
 * independent conditions — `!master || !scheduled` is `!(master && scheduled)`,
 * so false DOES win — and it is not the defect. The shape that must never
 * appear is the master POSITIVE term OR-ed with the schedule switch and then
 * re-tested against it. That shape is not reliably recognisable by reading
 * text, so these tests ask the question the operator asks: set the key, then
 * ask the provider what it did.
 */
final class BackupMasterSwitchTest extends TestCase
{
    private const PROVIDER = 'src/Laravel/StoreBackupServiceProvider.php';

    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    /**
     * The provider has exactly ONE reader of the master switch, and it returns
     * the key's own value.
     *
     * A single reader is the point: two readers that each fold the key
     * differently is how the two sites drifted into disagreeing about whether
     * it is honoured.
     */
    public function test_the_master_switch_has_exactly_one_reader_and_it_returns_the_keys_own_value(): void
    {
        $readers = self::readerLines('teleframe.store.backup.enabled');

        self::assertNotSame(
            [],
            $readers,
            'teleframe.store.backup.enabled has NO reader. It is back to being a dead key.'
        );

        self::assertCount(
            1,
            $readers,
            'the master switch must be read in exactly ONE place. Found readers at: '
            .implode(', ', $readers)."\nTwo readers that fold the key differently is how the"
            ."\nabsorption bug happened in the first place.",
        );

        $method = new ReflectionMethod(StoreBackupServiceProvider::class, 'masterEnabled');

        self::assertTrue(
            $method->isStatic(),
            'masterEnabled() must be static: it is called from a static closure and from'
            ."\nboot() with no instance.",
        );

        // A pure reader: config repository in, bool out. If it ever starts
        // composing the schedule switch, the key becomes inert again and every
        // behavioural test below inverts.
        self::assertSame(1, $method->getNumberOfParameters());
        self::assertSame(
            'bool',
            (string) $method->getReturnType(),
            'masterEnabled() must return bool, or the gates stop meaning what they say.',
        );
    }

    /**
     * SITE 1 — the derived 'backup' watchdog registration, asked the operator's
     * question directly against the real provider method.
     *
     * The middle rows are the bugs, stated as a truth table instead of as
     * algebra: master off, schedule on, must arm NOTHING; and — F13-1 — no
     * DECLARED TRIGGER means nothing is expected, so nothing can be reported
     * missing. The table grew a third switch when it grew that row, because a
     * truth table that cannot express the new condition cannot pin it either.
     */
    #[DataProvider('masterSwitchTruthTable')]
    public function test_the_watchdog_registration_follows_the_master_switch(
        bool $master,
        bool $schedule,
        bool $packageTrigger,
        bool $externalTrigger,
        bool $expectedArmed,
    ): void {
        $config = $this->app->make('config');
        $config->set('teleframe.store.backup.enabled', $master);
        $config->set('teleframe.store.backup.schedule.enabled', $schedule);
        $config->set('teleframe.store.backup.schedule.package_schedule.enabled', $packageTrigger);
        $config->set('teleframe.store.backup.schedule.external_trigger', $externalTrigger);

        $source = (new ReflectionMethod(StoreBackupServiceProvider::class, 'backupWatchSource'))
            ->invoke(null, $config);
        self::assertInstanceOf(\Closure::class, $source);

        $registrations = $source();

        $posture = 'master='.self::yn($master).' schedule='.self::yn($schedule)
            .' package_trigger='.self::yn($packageTrigger)
            .' external_trigger='.self::yn($externalTrigger);

        if ($expectedArmed) {
            self::assertArrayHasKey(
                'backup',
                $registrations,
                $posture.' must arm the backup watchdog.',
            );

            return;
        }

        self::assertSame(
            [],
            $registrations,
            $posture.' armed the backup watchdog anyway.'
            ."\nPre-fix, the 'master off + schedule on' row returned ['backup']: the key"
            ."\nwas read and then absorbed, which is the defect this class exists for."
            ."\nPost-F13-1, the same is true of a row with NO declared dump trigger: the"
            ."\npackage cannot see a crontab, so it does not claim to be waiting for a"
            ."\nheartbeat nothing in the package fires.",
        );
    }

    /**
     * @return iterable<string, array{bool, bool, bool, bool, bool}>
     */
    public static function masterSwitchTruthTable(): iterable
    {
        // The three switches the expectation actually answers to, then what it does.
        yield 'master off, schedule on — the absorbed-key defect' => [false, true, true, false, false];
        yield 'master on, schedule off' => [true, false, true, false, false];
        yield 'both switches off' => [false, false, true, true, false];

        // F13-1 — the alarm only exists where something fires the dump.
        yield 'shipped defaults: both switches on, NO declared trigger' => [true, true, false, false, false];
        yield 'the package trigger declared — evidence' => [true, true, true, false, true];
        yield 'an external trigger declared — testimony' => [true, true, false, true, true];
        yield 'either declaration, master off — master still wins' => [false, true, true, true, false];
        yield 'either declaration, schedule off — schedule still wins' => [true, false, true, true, false];
    }

    /**
     * F13-1 — THE SHIPPED DEFAULT ARMS NO ALARM. This is the defect in one
     * assertion: on a stock install the package registered a 'backup'
     * expectation and an hourly check for a heartbeat that nothing in the
     * package could produce, so every host on the shipped posture was told
     * "backup missing" forever, about a backup that was working.
     *
     * It asserts the DEFAULT, with no key set, so it fails if a future default
     * quietly re-arms it — and it is the row the truth table's shipped-default
     * entry cannot prove on its own, because that test SETS its values.
     */
    public function test_the_shipped_default_arms_no_backup_expectation(): void
    {
        $config = $this->app->make('config');

        self::assertTrue(
            (bool) $config->get('teleframe.store.backup.enabled'),
            'premise broken: this test is about a default install, where the master switch is on.',
        );
        self::assertTrue(
            (bool) $config->get('teleframe.store.backup.schedule.enabled'),
            'premise broken: this test is about a default install, where the schedule switch is on.',
        );
        self::assertFalse(
            (bool) $config->get('teleframe.store.backup.schedule.external_trigger'),
            'premise broken: external_trigger must ship false, or "no trigger declared" is unreachable.',
        );

        $source = (new ReflectionMethod(StoreBackupServiceProvider::class, 'backupWatchSource'))
            ->invoke(null, $config);
        self::assertInstanceOf(\Closure::class, $source);

        self::assertSame(
            [],
            $source(),
            'a default install must register NO backup watchdog expectation. With both'
            ."\nswitches on and no declared trigger, it registered ['backup' => 172800] and"
            ."\nthe hourly check then reported that heartbeat missing forever — for a dump"
            ."\nnothing in this package fires. Declare a trigger (schedule.external_trigger,"
            ."\nor schedule.package_schedule.enabled) to get the alarm back.",
        );
    }

    /**
     * F13-1 — AND THE ALARM IS ONE KEY AWAY, WITH NO SCHEDULE TO RE-EDIT.
     *
     * The fix must not have cost the capability. Declaring a trigger re-arms
     * the expectation at the shipped 48h window (daily interval × the shipped
     * multiplier of 2), and the hourly check — which is registered on the master
     * switch alone and was left that way — starts finding something to poll.
     */
    public function test_declaring_a_trigger_arms_the_expectation_at_the_shipped_window(): void
    {
        $config = $this->app->make('config');
        $config->set('teleframe.store.backup.schedule.external_trigger', true);

        $source = (new ReflectionMethod(StoreBackupServiceProvider::class, 'backupWatchSource'))
            ->invoke(null, $config);
        self::assertInstanceOf(\Closure::class, $source);

        $registrations = $source();

        self::assertArrayHasKey(
            'backup',
            $registrations,
            'declaring a trigger must arm the backup watchdog. F13-1 fixed a false alarm by'
            ."\nrequiring a declaration — if this cannot arm it, the fix removed the capability"
            ."\nwith the false positive.",
        );
        self::assertSame(
            172800,
            $registrations['backup'],
            'the window must stay the shipped one — daily interval (86400) × the shipped'
            ."\nwatchdog_expect_multiplier (2) — so the alarm fires on the schedule the host"
            ."\nwas already promised.",
        );

        // The poll survives the empty default: the check is gated on the master
        // switch alone, so a host that declares a trigger needs no second change.
        // Substring, not equality: a scheduled event's command carries the
        // interpreter and artisan prefix, which is a framework detail.
        $polled = array_values(array_filter(
            $this->scheduledCommands(),
            static fn (string $command): bool => str_contains($command, 'teleframe:notify watch --check'),
        ));

        self::assertNotSame(
            [],
            $polled,
            'the hourly check must stay registered on the master switch alone. With no'
            ."\ntrigger declared it has nothing to poll, which is the silence F13-1 wants;"
            ."\nunregistering it would make the alarm need a second change to come back.",
        );
    }

    private static function yn(bool $value): string
    {
        return $value ? 'on' : 'off';
    }

    /**
     * SITE 2 — the hourly `teleframe:notify watch --check` registration.
     *
     * This is the surface a host actually observes: with the master switch off
     * the command must not be in the scheduler's event list at all. Pre-fix it
     * was, because `$enabled = master || schedule.enabled` was true.
     */
    public function test_the_hourly_no_show_check_is_not_registered_when_the_master_switch_is_off(): void
    {
        $schedule = $this->scheduledCommands();

        self::assertNotSame(
            [],
            $schedule,
            'the shipped default is master=true, so the hourly check must be registered —'
            ."\notherwise this test cannot prove anything about turning it off.",
        );

        $schedule = $this->scheduledCommands(masterEnabled: false);

        self::assertSame(
            [],
            $schedule,
            'with the master switch OFF, `teleframe:notify watch --check` must not be'
            ."\nscheduled. Before the fix it was: the switch was folded into"
            ."\n'master || schedule.enabled', so false could not turn it off.",
        );
    }

    /**
     * Run the provider's boot-time scheduler registration against a real
     * Schedule and report which teleframe commands it registered.
     *
     * @return list<string>
     */
    private function scheduledCommands(bool $masterEnabled = true): array
    {
        $config = $this->app->make('config');
        $config->set('teleframe.store.backup.enabled', $masterEnabled);
        $config->set('teleframe.store.backup.schedule.enabled', true);

        $schedule = new Schedule;
        $this->app->instance(Schedule::class, $schedule);

        // The method is private (it is a boot() step, not an API), so it is
        // invoked the way boot() invokes it. Not a mock: this IS the shipping
        // registration, run against a real Schedule.
        (new ReflectionMethod(StoreBackupServiceProvider::class, 'scheduleBackupWatchdogCheck'))
            ->invoke(new StoreBackupServiceProvider($this->app));

        $commands = [];
        foreach ($schedule->events() as $event) {
            $commands[] = $event->command ?? '';
        }

        return array_values(array_filter(
            $commands,
            static fn (string $command): bool => str_contains($command, 'teleframe:'),
        ));
    }

    /**
     * The config comment must describe the code. It used to be a warning that
     * the key was inert; the audit that wrote it is now fixed, so a comment
     * still calling the switch unread is itself a lie in the other direction.
     */
    public function test_the_config_file_no_longer_advertises_an_inert_master_switch(): void
    {
        $comment = ConfigKeyRegistry::commentAbove(
            'store-backup.php',
            "'enabled' => (bool) env('TELEFRAME_STORE_BACKUP_ENABLED'",
        );

        self::assertNotSame('', $comment, 'the master switch lost its comment block');

        self::assertStringNotContainsString(
            'READ-BUT-IGNORED',
            $comment,
            'src/Laravel/config/store-backup.php still labels the master switch'
            ."\nREAD-BUT-IGNORED. It is not: the key is read by masterEnabled() and gates"
            ."\nboth call sites. A comment that outlives its fix is the audit lying again,"
            ."\njust in the other direction.",
        );

        // The positive claims, so the comment cannot simply be deleted, and so
        // it cannot quietly start describing a different switch.
        self::assertStringContainsString(
            'ONE',
            $comment,
            'the master switch comment must name the single reader it now has, so the'
            ."\nnext reader of this file is not left guessing which of the two truths"
            ."\n(present or past tense) is current.",
        );

        self::assertStringContainsString(
            'watchdog',
            $comment,
            'the master switch comment must say WHAT the switch governs. It gates the'
            ."\nwatchdog, not the dump, and a comment that implies otherwise is the"
            ."\nsame defect in the other direction.",
        );
    }

    /**
     * The non-comment lines that read a dotted config key, as file:line.
     *
     * @return list<string>
     */
    private static function readerLines(string $key): array
    {
        $hits = [];
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(ConfigKeyRegistry::repoRoot().'/src')
        );

        foreach ($files as $file) {
            if (! $file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $lines = file($file->getPathname());
            if ($lines === false) {
                continue;
            }

            foreach ($lines as $i => $line) {
                $trimmed = ltrim($line);
                foreach (['*', '//', '#', '/*'] as $prefix) {
                    if (str_starts_with($trimmed, $prefix)) {
                        continue 2;
                    }
                }

                if (str_contains($line, "'".$key."'")
                    && (str_contains($line, 'config->get(') || str_contains($line, "config('"))) {
                    $hits[] = str_replace(
                        ConfigKeyRegistry::repoRoot().'/',
                        '',
                        $file->getPathname()
                    ).':'.($i + 1);
                }
            }
        }

        return $hits;
    }
}
