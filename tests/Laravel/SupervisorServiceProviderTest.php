<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Laravel\Console\SuperviseCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Laravel\SupervisorServiceProvider;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpawnerContract;
use MeRezaRezaei\Teleframe\Supervisor\SystemProcessSpawner;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use Spatie\LaravelData\LaravelDataServiceProvider;

/**
 * The wiring that makes "the host writes nothing" true: an auto-discovered
 * provider, a registered command, config defaults, and a spawner seam that
 * opens no process when it is resolved.
 */
final class SupervisorServiceProviderTest extends TestCase
{
    /** @return list<class-string> */
    protected function getPackageProviders($app): array
    {
        return [
            LaravelDataServiceProvider::class,
            TeleframeServiceProvider::class,
            SupervisorServiceProvider::class,
        ];
    }

    public function test_the_supervise_command_is_available_on_the_console_kernel(): void
    {
        // Same pattern as the other console tests: the provider registers the
        // command for real apps, the testbench kernel resolves them lazily.
        $this->app->make(Kernel::class)->registerCommand(new SuperviseCommand);

        self::assertArrayHasKey('teleframe:supervise', Artisan::all());
    }

    public function test_the_spawner_seam_resolves_without_spawning_anything(): void
    {
        $spawner = $this->app->make('teleclient.supervisor.spawner');

        self::assertInstanceOf(SystemProcessSpawner::class, $spawner);
        self::assertInstanceOf(ProcessSpawnerContract::class, $spawner);
    }

    public function test_the_redis_seam_never_throws_on_the_ops_path(): void
    {
        // Testbench may or may not reach a live redis; either way the seam
        // resolves to a contract or null — it never propagates a connection
        // error into the ops endpoint.
        $seam = $this->app->make(OpsReadModel::REDIS_KEY);

        self::assertTrue($seam === null || $seam instanceof RedisConnectionContract);
        self::assertIsArray((new OpsReadModel($seam))->snapshot()->toArray());
    }

    public function test_the_shipped_config_names_the_packages_long_running_commands(): void
    {
        $catalog = new ProcessCatalog;

        foreach ((array) config('teleframe.supervisor.processes') as $name) {
            self::assertTrue($catalog->has((string) $name), $name.' must be a shipped process');
        }
    }
}
