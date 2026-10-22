<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Store\Backup\BackupException;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseDumper;
use MeRezaRezaei\Teleframe\Store\Backup\DatabaseTarget;
use MeRezaRezaei\Teleframe\Store\Backup\DumpWorkspace;
use Orchestra\Testbench\TestCase;
use ReflectionMethod;

/**
 * WHY `schedule.package_schedule.enabled` SHIPS FALSE (F13, 2026-10-16).
 *
 * The question this file answers was left open by F13-1 on purpose: that lane
 * fixed the DECLARATION (the watchdog no longer arms for a dump nothing can
 * fire) and deliberately did not touch the DEFAULT POSTURE. This file is the
 * gate for that posture, so "off" is a decision with a measured reason rather
 * than an oversight nobody re-examines.
 *
 * THE ARGUMENT IS NOT THE DOUBLE-FIRE ONE, and this file does not restate it.
 * The double-fire ruling (cron is invisible to the Laravel scheduler, so the
 * package cannot prove a default-on trigger would not collide) is real and
 * stands in docs/decisions.md D-045.5. But it is an argument about a host
 * that HAS configured things. This file is about the host that has not, which
 * is the stock install every reader of this repo is standing in — and on that
 * host the answer is mechanical, not a judgement call.
 *
 *   1. A stock install has `database.driver = ''`. The provider therefore
 *      binds a dumper that THROWS at dump() time, naming the key.
 *   2. So with the trigger ARMED, the scheduled command cannot succeed. It
 *      cannot dump anything, because there is no database to dump.
 *   3. But arming the trigger is ALSO what arms the 'backup' no-show
 *      expectation (F13-1 made the trigger the evidence the watchdog waits
 *      for). So the armed install watches a heartbeat that structurally
 *      cannot arrive.
 *
 *   4. Which is a guaranteed false `backup.missing` — manufactured by the
 *      package's own default, on every host that installed it.
 *
 * Default-ON would not "fill everyone's disk": it would give every stock host
 * a permanently red job plus an alarm about a backup that never could have
 * run. Both are worse than silence, and both are shipped BY the default
 * rather than chosen by the operator.
 *
 * EVERY ASSERTION IS BEHAVIOURAL. The dumper is resolved from the real
 * provider against the real stock config and then run against a real
 * DumpWorkspace in a real temp dir; the watchdog expectation is read from the
 * provider's own derived registration. Nothing here reads the source to
 * decide what the code does.
 */
final class StockInstallDumpTriggerDefaultPostureTest extends TestCase
{
    private string $spool;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->spool = sys_get_temp_dir().'/tframe-stock-posture-'.bin2hex(random_bytes(4));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->spool.'/*') ?: [] as $file) {
            @unlink($file);
        }
        @rmdir($this->spool);

        parent::tearDown();
    }

    /**
     * PREMISE, made explicit because every other assertion here rests on it:
     * the stock install really does ship an unconfigured driver. If a future
     * change gave `database.driver` a real default, the whole safety argument
     * in this class would have to be re-derived — so the gate fails loudly
     * rather than quietly proving something stale.
     */
    public function test_the_stock_install_ships_no_database_driver(): void
    {
        self::assertSame(
            '',
            (string) $this->app->make('config')->get('teleframe.store.backup.database.driver'),
            'this class argues that a default-ON trigger cannot dump on a stock install because'
            ."\nthere is no driver. If the driver now defaults to a real engine, that argument is"
            ."\nvoid and the posture has to be re-decided on its merits.",
        );
    }

    /**
     * GATE 1 — ARMED IS NOT THE SAME AS ABLE. With the trigger turned on and
     * nothing else configured, the dump still cannot run: the bound dumper
     * throws by name. This is the load-bearing fact, and it is why turning the
     * key on is not a harmless default.
     */
    public function test_an_armed_trigger_cannot_dump_on_a_stock_install(): void
    {
        $this->app->make('config')->set('teleframe.store.backup.schedule.package_schedule.enabled', true);

        $dumper = $this->app->make(BackupSupport::DUMPER_KEY);
        self::assertInstanceOf(DatabaseDumper::class, $dumper);

        try {
            $dumper->dump(new DatabaseTarget('mysql', 'appdb'), new DumpWorkspace($this->spool));
            self::fail('the stock install must not be able to dump; a driver default has changed the argument');
        } catch (BackupException $e) {
            self::assertStringContainsString(
                'teleframe.store.backup.database.driver',
                $e->getMessage(),
                'the refusal must NAME the key that fixes it, or an operator meets a red job with no next step.',
            );
        }

        self::assertSame(
            [],
            glob($this->spool.'/tfbk-*') ?: [],
            'a refused dump must leave no spool file behind. An armed trigger on a stock install is'
            ."\nrefused BEFORE any bytes are written, which is why it cannot consume disk.",
        );
    }

    /**
     * GATE 2 — AND ARMING IT ALSO ARMS AN ALARM FOR A HEARTBEAT THAT CANNOT
     * ARRIVE. This is the half that makes the posture a decision rather than a
     * default: the trigger is the evidence the watchdog waits for (F13-1), so
     * "on" buys a guaranteed false `backup.missing` on a host that never
     * configured a database. Both halves are asserted here together, because
     * either alone would be a weaker claim than it looks.
     */
    public function test_arming_the_trigger_arms_an_alarm_no_stock_dump_can_satisfy(): void
    {
        $config = $this->app->make('config');

        // The stock posture: master on, watchdog on, nothing declared.
        self::assertSame(
            [],
            $this->watchdogExpectations(),
            'with nothing declared the watchdog expects nothing — F13-1 fixed the dangling alarm, and'
            ."\nthis is the state the shipped default must keep.",
        );

        // Now the counterfactual this class exists to make expensive to adopt:
        // flip only the trigger key and the alarm comes back…
        $config->set('teleframe.store.backup.schedule.package_schedule.enabled', true);

        self::assertSame(
            ['backup' => 172800],
            $this->watchdogExpectations(),
            'the trigger IS the evidence the watchdog waits for, so arming it arms the expectation.',
        );

        // …while the dump that would satisfy it cannot run (GATE 1). The
        // expectation is therefore unsatisfiable on a stock install, and that
        // combination is what default-ON would ship to every host.
        $dumper = $this->app->make(BackupSupport::DUMPER_KEY);

        $this->expectException(BackupException::class);
        $dumper->dump(new DatabaseTarget('mysql', 'appdb'), new DumpWorkspace($this->spool));
    }

    /**
     * GATE 3 — THE OFF POSTURE IS THE ONE THAT IS SAFE TO SHIP, stated as the
     * positive claim rather than as the absence of a problem: with the shipped
     * default, the package registers no trigger, expects no heartbeat, and
     * leaves the decision to the operator. A default-ON trigger would have to
     * satisfy all three of these at once, and cannot.
     */
    public function test_the_shipped_posture_declares_nothing_and_expects_nothing(): void
    {
        $config = $this->app->make('config');

        self::assertFalse(
            (bool) $config->get('teleframe.store.backup.schedule.package_schedule.enabled'),
            'this posture is OFF. Turning it on is a disk/ops decision the operator must make'
            ."\nexplicitly, not something a package install decides for them.",
        );
        self::assertFalse(
            (bool) $config->get('teleframe.store.backup.schedule.external_trigger'),
            'no external trigger is declared by default either, so the watchdog has nothing to expect.',
        );
        self::assertSame(
            [],
            $this->watchdogExpectations(),
            'the shipped posture must arm no no-show expectation at all.',
        );
    }

    /**
     * The derived 'backup' no-show registration, as the provider builds it
     * from the live config.
     *
     * @return array<string, int>
     */
    private function watchdogExpectations(): array
    {
        $source = (new ReflectionMethod(StoreBackupServiceProvider::class, 'backupWatchSource'))
            ->invoke(null, $this->app->make('config'));
        self::assertInstanceOf(\Closure::class, $source);

        return $source();
    }
}
