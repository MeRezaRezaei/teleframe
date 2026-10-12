<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\App\AppException;
use MeRezaRezaei\Teleframe\App\AppRuntime;
use MeRezaRezaei\Teleframe\App\AppSupport;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Facades\TelegramApp;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The TelegramApp facade (design 2026-09-24 §3) — THE code face of the app
 * framework. The accessor is the fixed AppSupport::SERVICE_KEY seam; the
 * facade resolves an AppRuntime built by the REAL AppServiceProvider
 * (merged config, seeded registry, null schedule seam). Unconfigured
 * transport refuses LOUD on first use (never silent-drop).
 */
final class TelegramAppFacadeTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    public function test_facade_accessor_is_the_fixed_service_key(): void
    {
        self::assertSame(AppSupport::SERVICE_KEY, TelegramAppAccessorProbe::accessor());
        self::assertSame('teleclient.app.service', AppSupport::SERVICE_KEY);
    }

    public function test_facade_boots_against_the_real_provider_and_resolves_an_app_runtime(): void
    {
        $service = TelegramApp::getFacadeRoot();

        self::assertInstanceOf(AppRuntime::class, $service);
        self::assertSame($service, $this->app->make(AppSupport::SERVICE_KEY));
    }

    public function test_seeded_config_apps_are_visible_through_the_facade(): void
    {
        config(['teleframe.app.apps' => [
            [
                'name' => 'scheduled-bot',
                'mode' => 'user',
                'triggers' => [],
                'calls' => [
                    ['name' => 'status-ping', 'interval_seconds' => 3600, 'method' => 'messages.sendMessage', 'params' => ['message' => 'alive']],
                ],
            ],
        ]]);

        $service = TelegramApp::getFacadeRoot();

        self::assertTrue($service->registry()->has('scheduled-bot'));
        self::assertSame([
            'scheduled-bot' => ['name' => 'scheduled-bot', 'mode' => 'user', 'triggers' => 0, 'calls' => 1],
        ], $service->apps());
    }

    public function test_unconfigured_transport_refuses_loud_on_first_use(): void
    {
        config(['teleframe.app.apps' => [
            [
                'name' => 'loud-bot',
                'mode' => 'user',
                'triggers' => [],
                'calls' => [
                    ['name' => 'status-ping', 'interval_seconds' => 3600, 'method' => 'messages.sendMessage', 'params' => ['message' => 'alive']],
                ],
            ],
        ]]);

        $service = TelegramApp::getFacadeRoot();

        try {
            $service->runCall('loud-bot', 'status-ping');
            self::fail('an unconfigured transport must refuse loud on first use — never silent-drop');
        } catch (AppException $e) {
            self::assertStringContainsString('app transport', $e->getMessage());
            self::assertStringContainsString('cannot resolve user scope', $e->getMessage());
        }
    }
}

/**
 * Accessor probe: Facade::getFacadeAccessor() is protected, so a subclass
 * exposes the configured accessor for the seam assertion.
 */
final class TelegramAppAccessorProbe extends TelegramApp
{
    public static function accessor(): string
    {
        return self::getFacadeAccessor();
    }
}
