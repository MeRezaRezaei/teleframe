<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Vault;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase as BaseTestCase;

/**
 * Vault test base: testbench app + sqlite :memory: + the two vault
 * migrations run directly (same pattern as Identity\TestCase).
 */
abstract class TestCase extends BaseTestCase
{
    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('app.key', 'base64:' . base64_encode(str_repeat('v', 32)));
    }

    protected function setUp(): void
    {
        parent::setUp();

        $apps = require dirname(__DIR__, 2) . '/migrations/2026_09_09_000200_create_telegram_apps_table.php';
        $apps->up();

        $accounts = require dirname(__DIR__, 2) . '/migrations/2026_09_09_000201_create_telegram_accounts_table.php';
        $accounts->up();
    }
}
