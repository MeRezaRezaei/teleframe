<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Bot\Services\BotClient;
use MeRezaRezaei\Teleframe\Laravel\Facades\Notify;
use MeRezaRezaei\Teleframe\Laravel\NotifyServiceProvider;
use MeRezaRezaei\Teleframe\Notify\NotifyService;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * A BARE install proof: the package provider alone, nothing else bound.
 *
 * NotifyServiceProviderTest binds MessageCompiler + BotClient itself ("the
 * host app binds both"), which is correct for its offline-terminal focus but
 * hid the fact that a real host — which binds nothing — could not resolve
 * NotificationSender at all: BotClient takes a required `string $botToken`,
 * so the container threw BindingResolutionException and the Notify facade
 * was dead on arrival, token configured or not.
 *
 * This test deliberately binds NOTHING. No live Telegram, no credentials.
 */
final class NotifyBareInstallTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [NotifyServiceProvider::class];
    }

    public function test_notify_facade_resolves_on_a_bare_install_with_nothing_configured(): void
    {
        self::assertNull(config('teleframe.bot_token'));

        self::assertInstanceOf(NotifyService::class, Notify::getFacadeRoot());
    }

    public function test_the_bare_bot_client_is_inert_and_built_from_the_empty_configured_token(): void
    {
        $bot = $this->app->make(BotClient::class);

        self::assertInstanceOf(BotClient::class, $bot);
        self::assertSame('', $bot->botToken);
    }

    public function test_a_configured_token_reaches_the_bare_bot_client(): void
    {
        config(['teleframe.bot_token' => '123456:PLACEHOLDER-NOT-REAL']);

        self::assertSame('123456:PLACEHOLDER-NOT-REAL', $this->app->make(BotClient::class)->botToken);
    }
}
