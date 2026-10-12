<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\App;

use MeRezaRezaei\Teleframe\Handler\HandlerRegistry;
use MeRezaRezaei\Teleframe\Laravel\AppServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;

/**
 * The counter-case to AppReachabilityInDaemonProcessTest: the provider-boot
 * install (BREAK 1 fix) must cost an UNCONFIGURED host nothing and must not
 * need a bus, a database, a Redis connection or credentials.
 *
 * A host that never declared an app has an empty `teleframe.app.apps`; the
 * install loop's body must never run, the registry must never be resolved,
 * and the shared handler table must stay empty. This is what keeps a plain
 * console process (a queue worker with no Telegram in it) bootable.
 */
final class AppReachabilityUnconfiguredBootTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [
            TeleframeServiceProvider::class,
            AppServiceProvider::class,
        ];
    }

    protected function setUp(): void
    {
        HandlerRegistry::reset();

        parent::setUp();
    }

    protected function tearDown(): void
    {
        HandlerRegistry::reset();

        parent::tearDown();
    }

    public function test_an_unconfigured_host_registers_nothing(): void
    {
        self::assertSame(0, $this->app->make(HandlerRegistry::class)->count());
    }

    /** The registry is not even resolved when there is nothing to install. */
    public function test_an_unconfigured_host_does_not_resolve_the_app_seams(): void
    {
        self::assertFalse(
            $this->app->resolved('teleclient.app.registry'),
            'boot did not touch the registry — a config-only install costs nothing',
        );
        self::assertFalse($this->app->resolved('teleclient.app.transport'));
        self::assertFalse($this->app->resolved('teleclient.app.schedules'));
    }
}
