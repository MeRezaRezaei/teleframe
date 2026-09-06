<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Tests\Support;

use MeRezaRezaei\Teleframe\Laravel\Facades\Teleframe;
use MeRezaRezaei\Teleframe\Laravel\Facades\TF;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

final class FacadeSmokeTest extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders(mixed $app): array
    {
        return [TeleframeServiceProvider::class];
    }

    public function testTpFacadeResolvesRootCallable(): void
    {
        self::assertInstanceOf(TeleframeClient::class, TF::getFacadeRoot());
        self::assertInstanceOf(TeleframeClient::class, Teleframe::getFacadeRoot());
    }
}
