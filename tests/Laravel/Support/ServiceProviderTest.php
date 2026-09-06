<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests\Support;

use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeAuthService;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\TeleprotoServiceProvider;
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
        self::assertTrue($this->app->bound(TeleframeClient::class));
        self::assertTrue($this->app->bound(TeleframeAuthService::class));
        $keys = array_keys(config('teleproto'));
        sort($keys);
        self::assertContains('api_id', $keys);
        self::assertContains('dc_id', $keys);
    }
}
