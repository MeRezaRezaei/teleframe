<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\FileWatchAlertSink;
use MeRezaRezaei\Teleframe\Notify\NotifyCommand;
use Orchestra\Testbench\TestCase;
use Psr\SimpleCache\CacheInterface;
use ReflectionMethod;

/**
 * THE GUARD ON THE FALLBACK WATCHDOG'S DURABLE RECORD (F8, D-045.6).
 *
 * WatchdogDurableAlertTest proves NoShowWatchdog writes its record before it
 * notifies. tests/Config/WatchdogDurableAlertWiringTest proves the STORE-BACKUP
 * provider wires that sink. Neither can see the third path, and the third path
 * is the one a bare console takes:
 *
 *   NotifyCommand::watchdog() returns the shared singleton when a module binds
 *   FacadeSupport::WATCHDOG_KEY, else it calls buildWatchdog() — which
 *   constructed NoShowWatchdog with NO alertSink. That is the whole durable
 *   record gone on exactly the install with no provider: no derived backup
 *   registration, no FileWatchAlertSink, nothing but a PSR-3 line inside a
 *   host nobody outside is watching.
 *
 * It is WORSE than the pre-fix state, not merely equal to it. Once the sink
 * existed on the provider path, a reader of buildWatchdog() would reasonably
 * assume every watchdog carried one. A reader who assumed wrong would ship
 * believing the no-show was recorded. So this file pins the wiring on the
 * fallback itself, and it asserts it END-TO-END through the real artisan
 * command rather than by constructing the watchdog by hand — because the unit
 * under test is the command's path selection, and a hand-built watchdog cannot
 * prove which branch the command took.
 *
 * THE FALLBACK ITSELF IS NOT REDUNDANT (the deletion argument is backwards).
 * A real host with the package installed binds WATCHDOG_KEY and never reaches
 * this code; but a bare console / no-store-backup-module install always does.
 * Deleting the fallback would restore a sink-less watchdog, not remove a
 * duplicate one. GATE 1 below asserts the key really is unbound here, so this
 * file cannot quietly degrade into re-testing the provider path.
 */
final class WatchdogFallbackDurableAlertWiringTest extends TestCase
{
    /** The fallback WatchState's cache key: default prefix + topic. */
    private const WATCH_KEY = 'teleframe.notify.watch.backup';

    private string $logPath;

    private RecordingQueue $queue;

    protected function getPackageProviders(mixed $app): array
    {
        // NotifyServiceProvider ONLY. StoreBackupServiceProvider is exactly what
        // aliases WATCHDOG_KEY, so shipping it here would take the shared path
        // and the fallback under test would never run.
        return [NotifyServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $dir = sys_get_temp_dir().'/teleframe-watchdog-fallback-'.getmypid().'-'.bin2hex(random_bytes(4));
        mkdir($dir, 0700, true);
        $this->logPath = $dir.'/alerts.jsonl';

        // Offline: the alert must not reach a transport to be recorded. The
        // point under test is that the RECORD happens first, not that a
        // notification succeeds, so the queue is a recorder.
        $this->queue = new RecordingQueue;
        $this->app->instance(NotifyCommand::QUEUE_KEY, $this->queue);
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
     * GATE 1 — THIS FILE REALLY EXERCISES THE FALLBACK. Without it, a later
     * change that made NotifyServiceProvider alias WATCHDOG_KEY would turn
     * every gate below green while testing the provider path instead.
     */
    public function test_the_shared_watchdog_key_is_unbound_so_the_fallback_is_the_path_under_test(): void
    {
        self::assertFalse(
            $this->app->bound(FacadeSupport::WATCHDOG_KEY),
            'FacadeSupport::WATCHDOG_KEY is bound in this app, so NotifyCommand::watchdog() returns the'
            ."\nshared singleton and never calls buildWatchdog(). Every other gate in this file would then"
            ."\nbe passing for the wrong reason — this is the provider path, tested by"
            ."\ntests/Config/WatchdogDurableAlertWiringTest instead.",
        );
    }

    /**
     * GATE 2 — THE FALLBACK WRITES THE RECORD TO THE CONFIGURED PATH. Run
     * through artisan, on a heartbeat that is genuinely overdue, so the only
     * thing that can produce this file is the fallback's own sink.
     */
    public function test_the_fallback_watchdog_writes_a_durable_record_when_a_heartbeat_goes_missing(): void
    {
        $this->app->make('config')->set('teleframe.store.backup.watchdog.alert_log', $this->logPath);
        $this->expireTheBackupHeartbeat();

        $exit = $this->artisan('teleframe:notify', [
            'action' => 'watch',
            '--register' => 'backup',
            '--every' => 3600,
            '--check' => true,
        ])->run();

        self::assertSame(
            0,
            $exit,
            'teleframe:notify watch --check must succeed on a bare install. A non-zero exit means the'
            ."\ncommand took an error path, which is exactly the shape of a sink wired where it cannot be.",
        );

        self::assertFileExists(
            $this->logPath,
            'the FALLBACK watchdog found a no-show and wrote NO durable record. buildWatchdog() is the'
            ."\nbranch a bare console / no-store-backup-module install always takes, and it constructed"
            ."\nNoShowWatchdog with no alertSink — so its alert rides the queue it is watching and a"
            ."\nfailure of that queue takes the finding with it.",
        );

        $line = (string) file_get_contents($this->logPath);

        self::assertStringContainsString(
            '"kind":"'.FileWatchAlertSink::KIND.'"',
            $line,
            'the record must be stamped with the sink kind, or it cannot be told apart in a mixed log.',
        );

        self::assertStringContainsString(
            '"topic":"backup"',
            $line,
            'the record must name the topic that went missing, or the log cannot be acted on.',
        );

        self::assertCount(
            1,
            $this->queue->jobs,
            'exactly one alert is enqueued on the dedicated .missing topic — the notification is an'
            ."\nenhancement on top of the record, never a second finding.",
        );

        self::assertSame(
            'backup.missing',
            $this->queue->jobs[0]->topic,
            'the no-show alert must land on the dedicated {topic}.missing topic.',
        );
    }

    /**
     * GATE 3 — THE SHIPPED DEFAULT RESOLVES TO A USABLE PATH. With no key set,
     * the fallback must land under the host's storage/ directory, exactly as
     * the provider path does, so the config comment's promise holds on both
     * branches. A blank or temp-only resolution would either be refused by
     * FileWatchAlertSink or put the evidence somewhere a deploy can take.
     */
    public function test_the_fallback_default_alert_log_resolves_under_the_host_storage_directory(): void
    {
        $path = (new ReflectionMethod(NotifyCommand::class, 'alertLogPath'))
            ->invoke(null, $this->app);

        self::assertNotSame(
            '',
            trim($path),
            'the fallback default resolved blank. FileWatchAlertSink refuses a blank path, and unlike'
            ."\nthe provider there is no catch-all here to swallow it — the command would fail outright.",
        );

        self::assertStringEndsWith(
            'watchdog-alerts.jsonl',
            $path,
            'the fallback must use the documented filename, so a host reading the config comment finds'
            ."\nthe file where the comment says it is.",
        );

        self::assertStringStartsWith(
            $this->app->basePath('storage'),
            $path,
            'this host HAS an application base path, so the temp branch must not be reached here: a'
            ."\ndurable record in an ephemeral directory is exactly the record that is gone when"
            ."\nsomebody needs it.",
        );
    }

    /**
     * Put the durable last-seen far enough in the past to be overdue against
     * the registered expectation (3600 s), under the DEFAULT WatchState prefix
     * the fallback constructs with.
     */
    private function expireTheBackupHeartbeat(): void
    {
        $cache = $this->app->make(CacheInterface::class);
        self::assertInstanceOf(CacheInterface::class, $cache);

        $cache->set(self::WATCH_KEY, time() - 7_200);
    }
}
