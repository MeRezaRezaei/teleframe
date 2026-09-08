<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Identity;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase as BaseTestCase;

/**
 * Identity test base: testbench app (same providers as Schema\TestCase, so
 * the merged `teleframe` config exists and IdentityConfig reads resolve) +
 * sqlite :memory: + the Phase 5b binding migration run directly (the one
 * table this module needs; no curated dial, no provider migrate pass).
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
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('password')->nullable();
            $table->timestamps();
        });

        $migration = require dirname(__DIR__, 2) . '/migrations/2026_09_08_000100_create_tl_user_bindings_table.php';
        $migration->up();
    }
}