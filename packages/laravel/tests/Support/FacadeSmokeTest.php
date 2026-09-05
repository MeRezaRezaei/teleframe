<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleproto\Tests\Support;

use MeRezaRezaei\Teleproto\Facades\Teleproto;
use MeRezaRezaei\Teleproto\Facades\TP;
use MeRezaRezaei\Teleproto\Services\TeleprotoClient;
use MeRezaRezaei\Teleproto\TeleprotoServiceProvider;
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
        self::assertInstanceOf(TeleprotoClient::class, TP::getFacadeRoot());
        self::assertInstanceOf(TeleprotoClient::class, Teleproto::getFacadeRoot());
    }
}
