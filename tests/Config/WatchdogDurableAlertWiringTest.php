<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Config;

use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use Orchestra\Testbench\TestCase;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * THE GUARD ON THE PROVIDER WIRING OF THE OUT-OF-BAND ALERT (F8, S6).
 *
 * WatchdogDurableAlertTest proves the watchdog writes its durable record before
 * it notifies. This file proves the LAUNCHING HOST actually gets a watchdog
 * with that sink attached — which is a separate failure with the same silent
 * outcome, and one the unit file cannot see:
 *
 * `registerWatchdog()` wraps its whole body in `catch (Throwable) { return
 * null; }`, so a watchdog that constructs badly does not crash a boot. It
 * resolves to NULL, every heartbeat producer writes to nothing, and the no-show
 * detector does not exist. A config key that defaulted to '' and produced a
 * blank sink path would do exactly that: FileWatchAlertSink refuses a blank
 * path (GATE 5 in WatchdogDurableAlertTest), the refusal is swallowed by that
 * catch, and the watchdog silently stops being a watchdog.
 *
 * So the two gates here are: the wiring produces a real finding on disk at the
 * CONFIGURED path, and the shipped default ('' key) produces a non-blank path
 * rather than the null watchdog.
 */
final class WatchdogDurableAlertWiringTest extends TestCase
{
    private const WATCH_KEY = 'teleframe.notify.watch.backup';

    private string $logPath;

    protected function getPackageProviders(mixed $app): array
    {
        return [StoreBackupServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir().'/teleframe-watchdog-wiring-'.getmypid().'-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        $this->logPath = $dir.'/alerts.jsonl';
    }

    protected function tearDown(): void
    {
        if (is_file($this->logPath)) {
            unlink($this->logPath);
        }
        if (is_dir(dirname($this->logPath))) {
            rmdir(dirname($this->logPath));
        }

        parent::tearDown();
    }

    /**
     * GATE 1 — THE CONFIGURED PATH IS WHERE THE FINDING LANDS. Resolved from
     * the container, not constructed: this is the binding the package ships,
     * with its own catch-all, which is the thing that can null a watchdog.
     */
    public function test_the_provider_wires_the_durable_sink_to_the_configured_path(): void
    {
        $config = $this->app->make('config');
        $config->set('teleframe.store.backup.watchdog.alert_log', $this->logPath);

        // A DECLARED TRIGGER, and this is not incidental (F13-1). The derived
        // 'backup' expectation is gated on evidence that a dump will actually
        // fire, so a default install registers nothing — which is the fix for
        // the false "backup missing" alarm a stock install used to raise. This
        // gate is about the SINK, so it declares the cron posture explicitly
        // (TELEFRAME_STORE_BACKUP_EXTERNAL_TRIGGER=true: "a dump fires here, I
        // want to be told when it stops") and then proves the record lands.
        // Without the declaration the watchdog is correct and silent, which is
        // a different claim and is pinned in BackupMasterSwitchTest.
        $config->set('teleframe.store.backup.schedule.external_trigger', true);

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);

        self::assertInstanceOf(
            NoShowWatchdog::class,
            $watchdog,
            'the backup watchdog resolved to null. registerWatchdog() catches Throwable and returns'
            ."\nnull, so a wiring fault here does not fail a boot — it deletes the watchdog, and the"
            ."\nno-show the operator asked for stops existing.",
        );

        $this->expireTheBackupHeartbeat();

        $alerts = $watchdog->check();

        self::assertCount(
            1,
            $alerts,
            'the provider-built watchdog must detect the overdue backup heartbeat — that is the'
            ."\nwhole point of the derived registration, and it is what makes the record land.",
        );

        self::assertFileExists(
            $this->logPath,
            'the provider-built watchdog found a no-show and wrote NO durable record to the'
            ."\nconfigured path. The unit test proves the watchdog writes; this proves the"
            ."\nshipped watchdog — the one that actually runs hourly — is wired to do it.",
        );

        self::assertStringContainsString(
            '"topic":"backup"',
            (string) file_get_contents($this->logPath),
            'the record must name the topic that went missing, or the log cannot be acted on.',
        );
    }

    /**
     * GATE 2 — THE SHIPPED DEFAULT IS NOT A BLANK PATH. `''` must resolve to
     * somewhere writable under the host. If it resolved to '', the sink would
     * refuse, registerWatchdog()'s catch would return null, and the watchdog
     * would be gone on every default install — the out-of-band alert would
     * make the module strictly less reliable on the configuration that is
     * supposed to be its safest.
     */
    public function test_the_shipped_default_alert_log_resolves_to_a_usable_path(): void
    {
        $config = $this->app->make('config');

        self::assertSame(
            '',
            $config->get('teleframe.store.backup.watchdog.alert_log'),
            'the key ships empty, so the provider resolves the fallback — that is the posture this'
            ."\ngate is about.",
        );

        $path = (new ReflectionMethod(StoreBackupServiceProvider::class, 'alertLogPath'))
            ->invoke(null, $config, $this->app);

        self::assertNotSame(
            '',
            trim($path),
            'the default alert-log path resolved to a blank string. FileWatchAlertSink refuses a'
            ."\nblank path, registerWatchdog() swallows that refusal, and the watchdog becomes null"
            ."\n— no heartbeat, no check, no alert, with nothing failing.",
        );

        self::assertStringEndsWith(
            'watchdog-alerts.jsonl',
            $path,
            'the fallback must be the documented storage/teleframe/watchdog-alerts.jsonl, so a host'
            ."\nreading the config comment finds the file where the comment says it is.",
        );

        // The suffix alone is not the claim — the TEMP fallback has the same
        // filename. The claim is the directory, and this host HAS a base path,
        // so the temp branch must never be reached here: a durable record in an
        // ephemeral directory is exactly the record that is gone when somebody
        // needs it.
        self::assertStringStartsWith(
            $this->app->basePath('storage'),
            $path,
            'with an application base path present, the default alert log must live under'
            ."\nstorage/ as the config comment says — not in a temp directory that a reboot"
            ."\nor a deploy can take with it.",
        );
    }

    /**
     * Put the durable last-seen far enough in the past to be overdue against
     * the derived expectation (interval 86400 × multiplier 2 = 172 800 s).
     */
    private function expireTheBackupHeartbeat(): void
    {
        $cache = $this->app->make(CacheInterface::class);
        self::assertInstanceOf(CacheInterface::class, $cache);

        $cache->set(self::WATCH_KEY, time() - 200_000);
    }
}
