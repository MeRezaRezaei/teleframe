<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests\Support;

use MeRezaRezaei\Teleframe\Laravel\Facades\Teleproto;
use MeRezaRezaei\Teleframe\Laravel\Facades\TP;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\TeleprotoServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class FacadeSmokeTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [TeleprotoServiceProvider::class];
    }

    public function testTpFacadeResolvesRootCallable(): void
    {
        self::assertInstanceOf(TeleframeClient::class, TP::getFacadeRoot());
        self::assertInstanceOf(TeleframeClient::class, Teleproto::getFacadeRoot());
    }
}
