<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Control;

use Illuminate\Console\View\Components\Factory as ConsoleComponentsFactory;
use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\StreamSchema;
use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Models\TfAccount;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReflectionMethod;
use ReflectionProperty;
use Symfony\Component\Console\Output\BufferedOutput;

/**
 * Daemon hot re-seat (Cycle-5 item 1): the daemon's seated worker set is
 * mutable — a host adding or removing tf_accounts rows gets the workers
 * re-seated WITHOUT a restart, and unchanged accounts keep running (no
 * reconnect). The per-rotation registry re-read is the reliable path; a
 * tg:bus:reload {kind:'accounts'} payload is consumed best-effort when the
 * bus honors the loopback subscribe() semantic (ArrayRedis double).
 */
final class DaemonLiveReseatTest extends TestbenchTestCase
{
    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
    }

    private function migrateRegistry(): void
    {
        $this->artisan('migrate', [
            '--path' => dirname(__DIR__, 2).'/src/Laravel/Migrations/2026_09_22_000100_create_tf_accounts_table.php',
            '--realpath' => true,
        ])->assertExitCode(0);
    }

    private function persist(array $attributes): TfAccount
    {
        $account = new TfAccount($attributes);
        $account->save();

        return $account;
    }

    /**
     * A container-resolved DaemonCommand with a real components factory on a
     * buffer, so the reseat logging paths behave like an artisan run.
     */
    private function command(BufferedOutput $output): DaemonCommand
    {
        $command = $this->app->make(DaemonCommand::class);
        $command->setLaravel($this->app);

        $property = new ReflectionProperty(DaemonCommand::class, 'components');
        $property->setAccessible(true);
        $property->setValue($command, new ConsoleComponentsFactory($output));

        return $command;
    }

    private function invoke(object $target, string $method, mixed ...$args): mixed
    {
        $ref = new ReflectionMethod($target, $method);
        $ref->setAccessible(true);

        return $ref->invoke($target, ...$args);
    }

    private function reloadRequested(DaemonCommand $command): bool
    {
        $property = new ReflectionProperty(DaemonCommand::class, 'reloadRequested');
        $property->setAccessible(true);

        return (bool) $property->getValue($command);
    }

    public function test_daemon_seat_set_is_mutable_without_reconnecting_existing_workers(): void
    {
        $builds = 0;
        $daemon = new Daemon(
            accountsConfig: [['account_id' => 1]],
            workerFactory: function (array $config) use (&$builds): WorkerInterface {
                $builds++;

                return new CallbackWorker(static function (?callable $shouldStop = null): void {});
            },
        );

        $daemon->onLoop(function () use ($daemon): void {
            $daemon->seat(['account_id' => 2]);
            $daemon->seat(['account_id' => 1]); // already seated — must NOT rebuild
            $daemon->seat(['account_id' => 1]); // and not again
            $daemon->stop();
        });

        $exit = $daemon->run();

        self::assertSame(0, $exit);
        self::assertSame([1, 2], $daemon->seatedIds(), 'repeat seat() is a no-op that keeps the live set');
        self::assertSame(2, $builds, 'account 1 built once at boot and never rebuilt by repeat seat()');
    }

    public function test_seat_rejects_non_positive_account_ids_at_runtime(): void
    {
        $daemon = new Daemon(
            accountsConfig: [],
            workerFactory: static fn (array $config): WorkerInterface => throw new \LogicException('never built'),
        );

        $this->expectException(InvalidArgumentException::class);

        $daemon->seat(['account_id' => 0]);
    }

    public function test_plan_reseat_diffs_desired_against_seated_ids(): void
    {
        $command = $this->command(new BufferedOutput);

        $plan = $this->invoke(
            $command,
            'planReseat',
            [1 => ['account_id' => 1], 3 => ['account_id' => 3]],
            [1, 2],
        );

        self::assertSame([['account_id' => 3]], $plan['seat']);
        self::assertSame([2], $plan['stop']);
    }

    public function test_reload_payload_kind_filtering(): void
    {
        $accepted = $this->command(new BufferedOutput);
        $this->invoke(
            $accepted,
            'noteReloadPayload',
            json_encode(['kind' => 'accounts', 'account_id' => 7, 'change' => 'added']),
        );

        self::assertTrue($this->reloadRequested($accepted), 'a well-formed {kind:accounts} payload nudges the reseat');

        $foreign = $this->command(new BufferedOutput);
        $this->invoke($foreign, 'noteReloadPayload', json_encode(['kind' => 'bots', 'account_id' => 7]));

        self::assertFalse($this->reloadRequested($foreign), 'foreign reload kinds are ignored');

        $broken = $this->command(new BufferedOutput);
        $this->invoke($broken, 'noteReloadPayload', '{this is not json');

        self::assertFalse($this->reloadRequested($broken), 'malformed payloads are swallowed, never thrown');
    }

    /**
     * The Cycle-5 flagship: run the daemon loop while a host adds and then
     * removes a tf_accounts row. The reload payload nudges the rotation-top
     * reseat, which seats the added account's worker and stops the removed
     * one's — leaving the STABLE account running (single build, no restart).
     */
    public function test_reload_payload_re_seats_the_daemon_worker_set_without_restart(): void
    {
        $this->migrateRegistry();
        $this->persist([
            'id' => 1,
            'label' => 'stable',
            'dc_id' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:stable',
            'enabled' => true,
        ]);

        // Seed supplies dialable configs for BOTH ids; the registry drives
        // membership. No session_string anywhere — the fake factory never dials.
        config(['teleframe.daemon.accounts' => [
            ['account_id' => 1],
            ['account_id' => 2],
        ]]);

        $redis = new ArrayRedis;
        $this->app->instance(RedisConnectionContract::class, $redis);

        $reloads = [];
        $redis->subscribe(StreamSchema::RELOAD_CHANNEL, function (string $channel, string $payload) use (&$reloads): void {
            $reloads[] = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        });

        $state = new \stdClass;
        $state->runs = [];
        $state->builds = [];

        $hold = new \stdClass;
        $hold->daemon = null;

        $factory = function (array $config) use ($state, $hold, $redis): array {
            $accountId = (int) $config['account_id'];
            $state->builds[$accountId] = ($state->builds[$accountId] ?? 0) + 1;

            $worker = new CallbackWorker(function (?callable $shouldStop = null) use ($accountId, $state, $hold, $redis): void {
                $state->runs[$accountId] = ($state->runs[$accountId] ?? 0) + 1;

                if ($accountId !== 1) {
                    return; // account 2 ticks once between seat and unseat
                }

                $n = $state->runs[$accountId];

                if ($n === 1) {
                    $this->persist([
                        'id' => 2,
                        'label' => 'arriving',
                        'dc_id' => 2,
                        'kind' => 'account',
                        'session_ref' => 'vault:arriving',
                        'enabled' => true,
                    ]);

                    $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                        'kind' => 'accounts',
                        'account_id' => 2,
                        'change' => 'added',
                    ]));
                } elseif ($n === 2) {
                    TfAccount::query()->find(2)?->delete();

                    $redis->publish(StreamSchema::RELOAD_CHANNEL, json_encode([
                        'kind' => 'accounts',
                        'account_id' => 2,
                        'change' => 'removed',
                    ]));
                } elseif ($n === 3) {
                    $hold->daemon?->stop();
                }
            });

            return ['config' => $config, 'worker' => $worker, 'wrapper' => $worker];
        };
        $this->app->instance(DaemonCommand::WORKER_FACTORY_KEY, $factory);

        $command = $this->command(new BufferedOutput);
        $this->invoke($command, 'armReloadWatch');

        $hold->daemon = $this->invoke($command, 'buildDaemon', $this->invoke($command, 'resolveAccounts'));

        $exit = $hold->daemon->run();

        self::assertSame(0, $exit);
        self::assertSame([1 => 1, 2 => 1], $state->builds, 'stable account built once — hot re-seat never rebuilds it');
        self::assertSame([1 => 3, 2 => 1], $state->runs, 'worker 2 ticked between seat and unseat; worker 1 kept running');
        self::assertSame([1], $hold->daemon->seatedIds(), 'account 2 was unseated when its registry row disappeared');
        self::assertFalse($this->reloadRequested($command), 'the reload nudge was consumed');
        self::assertContains('accounts', array_column($reloads, 'kind'));
        self::assertContains(2, array_column($reloads, 'account_id'));
    }

    /**
     * Registry-config fallback (deliverable 1): with tf_accounts unmigrated,
     * resolveAccounts() returns the config seed, so a rotation-top reseat
     * against an empty registry keeps the seed-seated accounts running —
     * no spurious unseat or rebuild.
     */
    public function test_reseat_falls_back_to_config_seed_when_registry_is_not_migrated(): void
    {
        config(['teleframe.daemon.accounts' => [
            ['account_id' => 1],
            ['account_id' => 2],
        ]]);

        $redis = new ArrayRedis;
        $this->app->instance(RedisConnectionContract::class, $redis);

        $state = new \stdClass;
        $state->runs = [];
        $state->builds = [];

        $hold = new \stdClass;
        $hold->daemon = null;

        $factory = function (array $config) use ($state, $hold): array {
            $accountId = (int) $config['account_id'];
            $state->builds[$accountId] = ($state->builds[$accountId] ?? 0) + 1;

            $worker = new CallbackWorker(function (?callable $shouldStop = null) use ($accountId, $state, $hold): void {
                $state->runs[$accountId] = ($state->runs[$accountId] ?? 0) + 1;
                $hold->daemon?->stop();
            });

            return ['config' => $config, 'worker' => $worker, 'wrapper' => $worker];
        };
        $this->app->instance(DaemonCommand::WORKER_FACTORY_KEY, $factory);

        $command = $this->command(new BufferedOutput);

        $accounts = $this->invoke($command, 'resolveAccounts');
        self::assertCount(2, $accounts, 'unmigrated registry falls back to the two-seed config seed.');

        $hold->daemon = $this->invoke($command, 'buildDaemon', $accounts);

        $exit = $hold->daemon->run();

        self::assertSame(0, $exit);
        self::assertSame([1, 2], $hold->daemon->seatedIds(), 'seed accounts stay seated under an unmigrated registry');
        self::assertSame([1 => 1, 2 => 1], $state->builds, 'reseat against the empty registry rebuilds nothing');
    }
}

final class CallbackWorker implements WorkerInterface
{
    private readonly \Closure $body;

    public function __construct(callable $body)
    {
        $this->body = $body(...);
    }

    public function run(?callable $shouldStop = null): void
    {
        ($this->body)($shouldStop);
    }
}
