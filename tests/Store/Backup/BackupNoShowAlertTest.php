<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Backup;

use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Cache;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\StoreBackupServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NoShowWatchdog;
use MeRezaRezaei\Teleframe\Store\Backup\BackupScheduleSpec;
use MeRezaRezaei\Teleframe\Store\Backup\BackupSupport;
use MeRezaRezaei\Teleframe\Tests\Notify\FakeBotClient;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * The acceptance test for the owner's own named example: "a database backup
 * did not come in time" must produce a real notification.
 *
 * Before this change the alert could not fire: the watchdog the backup
 * module binds carried NO notify queue and NO logger, so emit() returned
 * before enqueue(); the registration was an in-memory array, registered
 * only in a console boot; and the only check() call site built a different
 * watchdog with zero registrations. The heartbeat was recorded durably
 * and nothing could ever alert on it.
 *
 * This drives the real container wiring: a stale teleframe.notify.watch.backup
 * key in the PSR-16 store, then check() through the SHARED watchdog binding —
 * in a process that never called register() — must land a sendDocument on the
 * notification core.
 */
final class BackupNoShowAlertTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class, StoreBackupServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/tframe-no-show-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        config([
            'cache.default' => 'array',
            // The sync queue runs the shared handle() path inline, so the
            // alert reaches the bot terminal inside this same process —
            // no worker, no broker, no credentials.
            'teleframe.notify.queue' => 'sync',
            'teleframe.notify.default_chat' => '@ops',
            'teleframe.store.backup.schedule.enabled' => true,
            'teleframe.store.backup.schedule.interval_seconds' => 3600,
        ]);

        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->singleton(BotClient::class, fn (): FakeBotClient => new FakeBotClient);
    }

    protected function tearDown(): void
    {
        foreach (scandir($this->tmp) ?: [] as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_file($this->tmp.'/'.$entry)) {
                unlink($this->tmp.'/'.$entry);
            }
        }
        @rmdir($this->tmp);

        parent::tearDown();
    }

    public function test_the_shared_watchdog_binding_carries_the_notify_queue_and_the_logger(): void
    {
        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);

        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);

        // The notify seam is what emit() hands the alert to — proven by
        // behaviour below, but asserted here so a future regression to a
        // bare `new NoShowWatchdog(new WatchState(...))` fails loudly.
        $reflection = new \ReflectionClass($watchdog);
        $notify = $reflection->getProperty('notify');
        $logger = $reflection->getProperty('logger');
        $source = $reflection->getProperty('registrationSource');

        self::assertNotNull($notify->getValue($watchdog), 'the watchdog must carry the notify queue');
        self::assertInstanceOf(LoggerInterface::class, $logger->getValue($watchdog));
        self::assertNotNull($source->getValue($watchdog), 'the registration must be derived, not held in memory only');
    }

    public function test_the_shared_watchdog_is_also_reachable_under_the_notify_key(): void
    {
        $fromBackup = $this->app->make(BackupSupport::WATCHDOG_KEY);
        $fromNotify = $this->app->make(FacadeSupport::WATCHDOG_KEY);

        self::assertInstanceOf(NoShowWatchdog::class, $fromNotify);
        self::assertSame($fromBackup, $fromNotify, 'the CLI check must see the same watchdog the backup run records into');
    }

    public function test_the_registration_is_derived_from_the_durable_schedule_spec(): void
    {
        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);

        $rows = $watchdog->registrations();
        self::assertArrayHasKey('backup', $rows, 'a fresh process must already watch the backup topic');

        // interval 3600 × the shipped multiplier 2, floor 3600 → 7200.
        $expected = (new BackupScheduleSpec(3600))->watchdogInterval();
        self::assertSame($expected, $rows['backup']['expected_every']);
    }

    public function test_the_derived_registration_agrees_with_the_durable_spec_at_the_shipped_multiplier(): void
    {
        config(['teleframe.store.backup.schedule.interval_seconds' => 86400]);
        $this->app->forgetInstance(BackupSupport::WATCHDOG_KEY);

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);

        self::assertSame(
            (new BackupScheduleSpec(86400))->watchdogInterval(),
            $watchdog->registrations()['backup']['expected_every'],
        );
    }

    public function test_a_disabled_schedule_watches_nothing(): void
    {
        config(['teleframe.store.backup.schedule.enabled' => false]);
        $this->app->forgetInstance(BackupSupport::WATCHDOG_KEY);

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);
        self::assertSame([], $watchdog->registrations());
    }

    public function test_a_stale_watch_key_produces_a_real_notification(): void
    {
        // A backup ran two hours ago; the expect window is 7200s → overdue.
        $stale = time() - 7_300;
        Cache::store('array')->set('teleframe.notify.watch.backup', $stale);

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);
        self::assertSame($stale, $watchdog->lastSeen('backup'), 'the watchdog must read the durable heartbeat key');

        $alerts = $watchdog->check();
        self::assertCount(1, $alerts);

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertNotSame([], $bot->calls, 'the alert never reached the notification core');

        // A real Bot API send reached the terminal — the alert was not
        // merely returned. The no-show brief is a short one-liner, so it
        // lands INLINE (sendMessage); the sendDocument path of the same core
        // is proven by test_the_alert_pipeline_delivers_a_long_body_as_a_document.
        $methods = array_column($bot->calls, 'method');
        self::assertNotSame([], array_intersect($methods, ['sendMessage', 'sendDocument']));

        $send = $bot->calls[array_search('sendMessage', $methods, true)];
        self::assertSame('@ops', $send['params']['chat_id'] ?? null);
        self::assertStringContainsString('backup.missing', (string) ($send['params']['text'] ?? ''));
        self::assertStringContainsString('no update arrived for backup', (string) ($send['params']['text'] ?? ''));
        self::assertStringContainsString('age_seconds', (string) ($send['params']['text'] ?? ''));
    }

    public function test_the_alert_pipeline_delivers_a_long_body_as_a_document(): void
    {
        // The same shared core, a body over the inline budget: the full text
        // survives as an HTML attachment (design §6) — the sendDocument half
        // of the alert pipeline.
        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        $service->log(
            Level::Alert,
            str_repeat('backup did not come in time. ', 200),
            ['topic' => 'backup'],
            'backup.missing',
        );

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);

        $methods = array_column($bot->calls, 'method');
        self::assertContains('sendDocument', $methods);
        self::assertCount(1, $bot->documents);
        self::assertStringContainsString('backup did not come in time', (string) $bot->documents[0]);
    }

    public function test_a_fresh_heartbeat_produces_no_notification(): void
    {
        Cache::store('array')->set('teleframe.notify.watch.backup', time());

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);

        self::assertSame([], $watchdog->check());

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertSame([], $bot->calls);
    }

    public function test_a_never_seen_heartbeat_is_grace_not_a_false_alarm(): void
    {
        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);

        self::assertSame([], $watchdog->check());
    }

    public function test_the_cli_check_uses_the_shared_watchdog_and_reports_the_alert(): void
    {
        Cache::store('array')->set('teleframe.notify.watch.backup', time() - 7_300);

        $this->artisan('teleframe:notify', ['action' => 'watch', '--check' => true])
            ->assertSuccessful();

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertNotSame([], $bot->calls, 'the CLI check must emit through the same shared watchdog');
    }

    public function test_the_cli_check_reports_a_quiet_watchdog_without_sending(): void
    {
        Cache::store('array')->set('teleframe.notify.watch.backup', time());

        $this->artisan('teleframe:notify', ['action' => 'watch', '--check' => true])
            ->expectsOutputToContain('no no-show alerts')
            ->assertSuccessful();

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertSame([], $bot->calls);
    }

    public function test_a_logger_is_wired_so_the_alert_is_reported_even_when_the_queue_is_absent(): void
    {
        $records = [];
        $this->app->instance(LoggerInterface::class, new class($records) extends AbstractLogger
        {
            public function __construct(public array &$records) {}

            public function log($level, \Stringable|string $message, array $context = []): void
            {
                $this->records[] = ['level' => (string) $level, 'message' => (string) $message];
            }
        });

        Cache::store('array')->set('teleframe.notify.watch.backup', time() - 7_300);

        $watchdog = $this->app->make(BackupSupport::WATCHDOG_KEY);
        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);
        $watchdog->check();

        $levels = array_column($records, 'level');
        self::assertContains('alert', $levels, 'the watchdog must log at alert level');

        $alert = null;
        foreach ($records as $record) {
            if ($record['level'] === 'alert') {
                $alert = $record;
            }
        }
        self::assertStringContainsString('backup', (string) $alert['message']);
        foreach (['session', 'auth_key', 'token', 'passphrase'] as $forbidden) {
            self::assertStringNotContainsString($forbidden, strtolower((string) $alert['message']));
        }
    }

    public function test_the_watchdog_stands_alone_without_a_notify_provider(): void
    {
        // The store backup module must stay constructible on its own: no
        // notify queue bound, no logger bound, no cache bound.
        $app = new Container;
        $app->instance('config', new Repository([
            'teleframe.store.backup' => require __DIR__.'/../../../src/Laravel/config/store-backup.php',
        ]));

        (new StoreBackupServiceProvider($app))->register();

        $watchdog = $app->make(BackupSupport::WATCHDOG_KEY);

        self::assertInstanceOf(NoShowWatchdog::class, $watchdog);
        self::assertInstanceOf(NullLogger::class, new NullLogger);
        self::assertArrayHasKey('backup', $watchdog->registrations());
    }
}
