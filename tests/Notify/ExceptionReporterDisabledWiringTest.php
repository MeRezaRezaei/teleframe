<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use Illuminate\Contracts\Debug\ExceptionHandler;
use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\Notify\ExceptionReporter;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Message\MessageCompiler;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use RuntimeException;

/**
 * DISABLED MEANS DISABLED — at the provider, not just in the unit test.
 *
 * This is a separate class on purpose: teleframe.notify.exceptions.enabled
 * is read when the provider boots, so a "turn it off mid-test" toggle would
 * test nothing. The shipped default is false, so this is also the shape of
 * every install that never opted in: a real Laravel exception handler, an
 * unhandled exception, and silence.
 */
final class ExceptionReporterDisabledWiringTest extends TestbenchTestCase
{
    private string $tmp;

    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    protected function getEnvironmentSetUp(mixed $app): void
    {
        // A target chat IS configured: the reporter is off by DECISION, not
        // by accident of an unconfigured install.
        $app['config']->set('teleframe.notify.default_chat', '@ops');
        $app['config']->set('teleframe.notify.exceptions.enabled', false);
    }

    protected function setUp(): void
    {
        $this->tmp = sys_get_temp_dir().'/tframe-notify-exc-off-'.bin2hex(random_bytes(4));
        mkdir($this->tmp, 0777, true);

        parent::setUp();

        $this->app->singleton(MessageCompiler::class, fn (): MessageCompiler => new MessageCompiler($this->tmp.'/tpl', $this->tmp.'/cache'));
        $this->app->singleton(BotClient::class, fn (): FakeBotClient => new FakeBotClient);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmp);

        parent::tearDown();
    }

    public function test_the_bound_reporter_is_inert_on_a_shipped_default_install(): void
    {
        self::assertFalse($this->app->make(ExceptionReporter::class)->enabled());
    }

    public function test_an_unhandled_exception_reaches_nothing_when_reporting_is_off(): void
    {
        $this->app->make(ExceptionHandler::class)->report(new RuntimeException('boom'));

        $bot = $this->app->make(BotClient::class);
        self::assertInstanceOf(FakeBotClient::class, $bot);
        self::assertSame(0, $bot->attempts, 'a disabled reporter must be inert');
        self::assertSame([], $bot->calls);
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
