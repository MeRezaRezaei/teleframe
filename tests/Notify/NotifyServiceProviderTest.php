<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NotificationLogger;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use MeRezaRezaei\Teleframe\Notify\Psr3NotifyBridge;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use Psr\Log\LoggerInterface;

/**
 * Provider boot (register() + bindings) inside a Testbench app: the
 * NotifyServiceProvider registers with zero TypeErrors against the REAL
 * shipped Notify module classes, the facade accessor resolves a
 * NotifyService, config topic rows seed the Topic registry, and the sync
 * queue handler runs the shared handle() path inline into an offline
 * FakeBotClient terminal. No live Telegram, no credentials.
 */
final class NotifyServiceProviderTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir().'/tframe-notify-provider-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        // The provider resolve NotificationSender (MessageCompiler +
        // BotClient) from the container — the host app binds both. Wire
        // offline fakes here, mirroring the end-to-end fixture pattern.
        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->singleton(BotClient::class, fn (): FakeBotClient => new FakeBotClient);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
    }

    public function test_register_runs_and_the_facade_accessor_resolves_a_notify_service(): void
    {
        config(['teleframe.notify.default_chat' => '@ops']);

        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        self::assertInstanceOf(NotifyService::class, $service);
        self::assertInstanceOf(NotificationSender::class, $this->app->make(NotificationSender::class));
    }

    public function test_config_topic_rows_seed_the_registry_over_the_defaults(): void
    {
        config(['teleframe.notify.default_chat' => '@ops', 'teleframe.notify.topics' => [
            ['name' => 'app.info', 'level' => 'info', 'thread_id' => 2],
            ['name' => 'ops', 'level' => 'warning', 'chat' => '@alerts', 'thread_id' => 3, 'throttle_per_hour' => 12],
        ]]);

        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        $rows = $service->registry()->topics();

        // Predefined rows survive (Topic::defaults()); the shipped app.*
        // row picks up the default chat, the host row carries its own.
        self::assertArrayHasKey('backup', $rows);
        self::assertSame('@ops', $rows['app.info']->chat());
        self::assertSame(2, $rows['app.info']->threadId());
        self::assertSame('@alerts', $rows['ops']->chat());
        self::assertSame(3, $rows['ops']->threadId());
        self::assertSame(12, $rows['ops']->throttlePerHour());
        self::assertSame('warning', $rows['ops']->level()->value);
    }

    public function test_sync_queue_handler_runs_the_shared_handle_inline_into_the_bot_terminal(): void
    {
        config(['teleframe.notify.default_chat' => '@ops']);

        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        $service->log(Level::Info, 'provider probe', ['src' => 'test']);

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertCount(1, $bot->calls);
        self::assertSame('sendMessage', $bot->calls[0]['method']);
        self::assertSame('@ops', $bot->calls[0]['params']['chat_id']);
        self::assertSame(2, $bot->calls[0]['params']['message_thread_id']);
        self::assertStringContainsString('[INFO] app.info', (string) $bot->calls[0]['params']['text']);
        self::assertStringContainsString('src=test', (string) $bot->calls[0]['params']['text']);
    }

    public function test_the_logger_half_is_bound_and_a_psr3_call_reaches_a_telegram_send(): void
    {
        config(['teleframe.notify.default_chat' => '@ops']);

        // The regression gate for the "logger system" defect: BOTH classes
        // shipped with ZERO construction sites anywhere in src/, so a stock
        // install could not point a log channel at this package without
        // hand-writing the bridge. Both resolve now, from their fixed
        // teleclient.notify.* keys AND their FQCNs.
        $bridge = $this->app->make(FacadeSupport::PSR3_KEY);
        self::assertInstanceOf(Psr3NotifyBridge::class, $bridge);
        self::assertInstanceOf(Psr3NotifyBridge::class, $this->app->make(Psr3NotifyBridge::class));

        $logger = $this->app->make(FacadeSupport::LEVEL_LOGGER_KEY);
        self::assertInstanceOf(NotificationLogger::class, $logger);
        self::assertInstanceOf(NotificationLogger::class, $this->app->make(NotificationLogger::class));

        // The bridge is NOT the host's LoggerInterface — binding it there
        // would feed notify's own internal warnings back into a send.
        self::assertNotInstanceOf(
            Psr3NotifyBridge::class,
            $this->app->bound(LoggerInterface::class) ? $this->app->make(LoggerInterface::class) : null,
        );

        // And it is not decoration: an ordinary PSR-3 warning becomes a
        // notification on its level's predefined topic, through the same
        // send path every other notification uses.
        $bridge->warning('psr3 seam is live', ['src' => 'test']);

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertCount(1, $bot->calls);
        self::assertSame('sendMessage', $bot->calls[0]['method']);
        self::assertSame(4, $bot->calls[0]['params']['message_thread_id']); // app.warning
        self::assertStringContainsString('[WARNING] app.warning', (string) $bot->calls[0]['params']['text']);
        self::assertStringContainsString('psr3 seam is live', (string) $bot->calls[0]['params']['text']);
    }

    public function test_the_rate_guard_is_advisory_by_default_and_enforcement_is_the_opt_in(): void
    {
        config(['teleframe.notify.default_chat' => '@ops']);

        $service = $this->app->make(FacadeSupport::SERVICE_KEY);

        // F1-2: the shipped per_chat_rate row is Telegram's REAL 1 msg/s, so
        // an ENFORCED default raised a NotifyException out of a stock
        // install's SECOND log call in the same second. The guard itself is
        // unchanged — it just advises by default and reports the verdict.
        $property = new \ReflectionProperty(NotifyService::class, 'enforceBudgets');

        self::assertFalse(
            $property->getValue($service),
            'the shipped posture must not throw on ordinary use',
        );

        $first = $service->log(Level::Info, 'first', ['n' => 1]);
        $second = $service->log(Level::Info, 'second', ['n' => 2]);

        self::assertNotSame('', $first);
        self::assertNotSame('', $second, 'advisory mode must still send, not drop');

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertCount(2, $bot->calls);

        // Enforcement is one key away, and it is LOUD when opted into. The
        // counters live in the host's PSR-16 store, so they survive the
        // re-resolve and the already-counted sends still count: the THIRD
        // send of the second is the one this refuses.
        config(['teleframe.notify.budgets.enforce' => true]);
        $this->app->forgetInstance(FacadeSupport::SERVICE_KEY);

        $strict = $this->app->make(FacadeSupport::SERVICE_KEY);
        self::assertTrue(
            (new \ReflectionProperty(NotifyService::class, 'enforceBudgets'))->getValue($strict),
            'the opt-in must actually flip the posture',
        );

        $this->expectException(NotifyException::class);
        $strict->log(Level::Info, 'third', ['n' => 3]);
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir.'/'.$entry;
            if (is_dir($path)) {
                $this->removeDir($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}
