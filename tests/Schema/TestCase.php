<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * Boots a minimal real Laravel app so config()/app()/spatie-laravel-data
 * behave exactly as in a host application.
 */
abstract class TestCase extends TestbenchTestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [\Spatie\LaravelData\LaravelDataServiceProvider::class, \MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider::class];
    }
}
