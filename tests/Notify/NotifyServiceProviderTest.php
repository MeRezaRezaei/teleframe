<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use MeRezaRezaei\Teleframe\Notify\FacadeSupport;
use MeRezaRezaei\Teleframe\Notify\Level;
use MeRezaRezaei\Teleframe\Notify\NotificationSender;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

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
