<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Http\Dashboard;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard\DashboardRoutes;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase as BaseTestCase;

/**
 * Dashboard test base: testbench app + sqlite :memory: + a host `users`
 * table plus the two vault migrations. Dashboard routes are registered per
 * test (DashboardRoutes::register is a one-shot guard, so reset() between
 * tests keeps each scenario on its own prefix/middleware).
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
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('v', 32)));
        $app['config']->set('auth.providers.users.model', Support\TestUser::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->timestamps();
        });

        $apps = require dirname(__DIR__, 4).'/src/Laravel/Migrations/2026_09_09_000200_create_telegram_apps_table.php';
        $apps->up();

        $accounts = require dirname(__DIR__, 4).'/src/Laravel/Migrations/2026_09_09_000201_create_telegram_accounts_table.php';
        $accounts->up();

        DashboardRoutes::reset();
    }

    protected function tearDown(): void
    {
        DashboardRoutes::reset();
        parent::tearDown();
    }
}
