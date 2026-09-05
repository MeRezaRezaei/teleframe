<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleproto\Tests\Support;

use MeRezaRezaei\Teleproto\Services\TeleprotoAuthService;
use MeRezaRezaei\Teleproto\Services\TeleprotoClient;
use MeRezaRezaei\Teleproto\TeleprotoServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class ServiceProviderTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [TeleprotoServiceProvider::class];
    }

    public function testConfigIsMergedAndBindingsRegistered(): void
    {
        self::assertSame(2, config('teleproto.dc_id'));
        self::assertTrue($this->app->bound(TeleprotoClient::class));
        self::assertTrue($this->app->bound(TeleprotoAuthService::class));
        $keys = array_keys(config('teleproto'));
        sort($keys);
        self::assertContains('api_id', $keys);
        self::assertContains('dc_id', $keys);
    }
}
