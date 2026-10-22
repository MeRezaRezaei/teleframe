<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Console;

use Illuminate\Contracts\Console\Kernel;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Laravel\Console\SuperviseCommand;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use MeRezaRezaei\Teleframe\Supervisor\ProcessCatalog;
use MeRezaRezaei\Teleframe\Supervisor\ProcessSpec;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use MeRezaRezaei\Teleframe\Tests\Support\FakeProcessSpawner;

/**
 * `teleframe:supervise` — the package's own process supervisor, driven
 * through the real command with the process seam REBOUND to a plain-PHP
 * fake, so no long-running process is ever started in a test.
 *
 * The acceptance shape under test is the user's own: one package-owned
 * command, a config block that ships with the package, and no host file to
 * paste (no horizon.php, no polling job).
 */
final class SuperviseCommandTest extends TestCase
{
    private FakeProcessSpawner $spawner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->spawner = new FakeProcessSpawner;
        $this->app->instance('teleclient.supervisor.spawner', $this->spawner);

        $this->app->make(Kernel::class)->registerCommand(new SuperviseCommand);
    }

    public function test_the_default_configuration_ships_the_package_own_processes(): void
    {
        // Nothing to configure: the shipped config names the long-running
        // commands, which is the whole point of the cycle.
        self::assertSame(['daemon', 'ingest'], config('teleframe.supervisor.processes'));
        self::assertSame(5, (int) config('teleframe.supervisor.restart_delay_seconds'));
    }

    public function test_one_command_supervises_the_configured_processes(): void
    {
        $this->artisan('teleframe:supervise --once')
            ->assertSuccessful();

        self::assertSame(2, $this->spawner->spawns, 'daemon + ingest are both started by the one command');
        self::assertSame('daemon', $this->spawner->spawned[0]->name);
        $argv = $this->spawner->spawned[0]->command;
        self::assertSame('teleframe:daemon', end($argv));
        self::assertStringEndsWith('artisan', $argv[count($argv) - 2], 'the supervisor runs a real artisan process, not a shell string');
    }

    public function test_a_crashed_process_is_restarted_by_the_command_loop(): void
    {
        $this->app['config']->set('teleframe.supervisor.restart_delay_seconds', 0);

        $this->artisan('teleframe:supervise --once')->assertSuccessful();
        self::assertSame(1, $this->spawner->howManyTimesSpawned('ingest'));

        // The ingest worker dies; the next bounded loop restarts it.
        $this->spawner->crash('ingest', exitCode: 1);
        $this->artisan('teleframe:supervise --once --process=ingest')->assertSuccessful();

        self::assertSame(2, $this->spawner->howManyTimesSpawned('ingest'), 'the command restarted what died');
    }

    public function test_an_unknown_process_name_fails_loudly_instead_of_supervising_nothing(): void
    {
        $this->artisan('teleframe:supervise --once --process=ingst')
            ->expectsOutputToContain('Unknown supervised process "ingst"')
            ->assertFailed();

        self::assertSame(0, $this->spawner->spawns, 'a refused name starts nothing');
    }

    public function test_a_misconfigured_process_name_also_fails_loudly(): void
    {
        $this->app['config']->set('teleframe.supervisor.processes', ['daemon', 'nope']);

        $this->artisan('teleframe:supervise --once')
            ->expectsOutputToContain('Unknown supervised process "nope"')
            ->assertFailed();
    }

    public function test_status_prints_the_ops_snapshot_without_starting_a_process(): void
    {
        $redis = new ArrayRedis;
        $redis->xadd(StreamSchema::DL, '*', [
            'account_id' => '5',
            'update' => '{}',
            'ts' => '1720000000',
            'reason' => 'ingest-failed',
            'error' => 'boom',
        ]);
        $this->app->instance(OpsReadModel::REDIS_KEY, $redis);

        $this->artisan('teleframe:supervise --status')
            ->expectsOutputToContain('dead-letter')
            ->assertSuccessful();

        self::assertSame(0, $this->spawner->spawns, '--status never starts a process');
    }

    public function test_a_host_can_supervise_its_own_long_running_command(): void
    {
        $this->app->instance(ProcessCatalog::SPECS_KEY, [
            new ProcessSpec(name: 'my-worker', command: ['php', 'worker.php'], restartDelaySeconds: 0),
        ]);
        $this->app['config']->set('teleframe.supervisor.processes', ['my-worker']);

        $this->artisan('teleframe:supervise --once')->assertSuccessful();

        self::assertSame(1, $this->spawner->howManyTimesSpawned('my-worker'));
    }
}
