<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Daemon;

use MeRezaRezaei\Teleframe\Laravel\Console\DaemonCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use Orchestra\Testbench\TestCase as TestbenchTestCase;
use ReflectionMethod;
use ReflectionProperty;

/**
 * DEFECT-5 — hot reload did not apply a CHANGED account config.
 *
 * `planReseat()` had two cases and not the third. It seated ids that were ABSENT
 * and stopped ids that were REMOVED, so an account whose `tf_accounts` row was
 * EDITED stayed seated with its old worker — and nothing downstream could notice:
 * `Daemon::seat()` early-returns for an already-seated id, and so does
 * `seatWorker()`. The plan came out `seat=[] stop=[]` and the stale worker kept
 * running.
 *
 * The case that matters most is the one that stayed invisible: a ROTATED SESSION.
 * The operator rotates a credential, `AccountLifecycleObserver::updated()`
 * publishes `change: 'changed'` on the reload channel, `noteReloadPayload()` flips
 * its boolean regardless of `change` — and the new session does not take effect
 * until the daemon is restarted by hand. The reload signal that specifically
 * announced a rotation was treated exactly like an add.
 *
 * A CHANGED id therefore goes into `stop` like a removed one, and the rotation
 * after that seats it again from the new config.
 */
final class DaemonReseatChangedConfigTest extends TestbenchTestCase
{
    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    // ── the defect ──────────────────────────────────────────────────────

    public function test_a_seated_account_whose_config_changed_is_stopped_so_it_can_be_rebuilt(): void
    {
        $command = $this->command();

        // What this daemon seated, recorded the way seatWorker() records it.
        $this->recordSeatedConfig($command, 1, [
            'account_id' => 1,
            'label' => 'primary',
            'dc' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:ORIGINAL',
        ]);

        // The row was edited: the session ref rotated.
        $plan = $this->planReseat($command, [
            1 => [
                'account_id' => 1,
                'label' => 'primary',
                'dc' => 2,
                'kind' => 'account',
                'session_ref' => 'vault:ROTATED',
            ],
        ], [1]);

        self::assertSame([], $plan['seat'], 'it is already seated; the fix is a stop, not a seat');
        self::assertSame([1], $plan['stop'],
            'a CHANGED account must be stopped, or the rotated session never takes effect until a restart');
    }

    // ── what must NOT change ────────────────────────────────────────────

    public function test_an_unchanged_seated_account_is_left_running(): void
    {
        $command = $this->command();

        $config = [
            'account_id' => 1,
            'label' => 'primary',
            'dc' => 2,
            'kind' => 'account',
            'session_ref' => 'vault:stable',
        ];

        $this->recordSeatedConfig($command, 1, $config);

        $plan = $this->planReseat($command, [1 => $config], [1]);

        self::assertSame([], $plan['seat']);
        self::assertSame([], $plan['stop'], 'an unchanged account must never be torn down — that is the '
            .'"no reconnect" promise the hot re-seat exists to keep');
    }

    public function test_a_seated_account_with_no_recorded_config_is_left_alone(): void
    {
        // A worker built by some other path has no recorded config in this
        // command. "Cannot compare" is not "changed" — tearing it down on a
        // comparison this process cannot make would be the worse bug.
        $command = $this->command();

        $plan = $this->planReseat($command, [1 => ['account_id' => 1, 'session_ref' => 'vault:x']], [1]);

        self::assertSame([], $plan['stop']);
        self::assertSame([], $plan['seat']);
    }

    public function test_key_order_alone_is_not_a_config_change(): void
    {
        $command = $this->command();

        $this->recordSeatedConfig($command, 1, [
            'account_id' => 1,
            'label' => 'primary',
            'session_ref' => 'vault:stable',
        ]);

        $plan = $this->planReseat($command, [
            1 => [
                'session_ref' => 'vault:stable',
                'label' => 'primary',
                'account_id' => 1,
            ],
        ], [1]);

        self::assertSame([], $plan['stop'], 'the same values in another order are the same config');
    }

    public function test_added_and_removed_accounts_still_diff_as_before(): void
    {
        $command = $this->command();

        $this->recordSeatedConfig($command, 1, ['account_id' => 1, 'session_ref' => 'vault:stable']);
        $this->recordSeatedConfig($command, 2, ['account_id' => 2, 'session_ref' => 'vault:stable']);

        $plan = $this->planReseat($command, [
            1 => ['account_id' => 1, 'session_ref' => 'vault:stable'],
            3 => ['account_id' => 3, 'session_ref' => 'vault:new'],
        ], [1, 2]);

        self::assertSame([['account_id' => 3, 'session_ref' => 'vault:new']], $plan['seat']);
        self::assertSame([2], $plan['stop']);
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function command(): DaemonCommand
    {
        $command = $this->app->make(DaemonCommand::class);
        $command->setLaravel($this->app);

        return $command;
    }

    /**
     * Populate the daemon's seated-worker map the way `seatWorker()` does.
     *
     * The worker/wrapper members are placeholders: the diff reads `config` only,
     * and building a real wrapper here would test `buildWorker()` instead.
     *
     * @param  array<string, mixed>  $config
     */
    private function recordSeatedConfig(DaemonCommand $command, int $accountId, array $config): void
    {
        $property = new ReflectionProperty(DaemonCommand::class, 'workerEntries');
        $property->setAccessible(true);

        /** @var array<int, array{config: array<string, mixed>, worker: object, wrapper: object}> $entries */
        $entries = $property->getValue($command);
        $entries[$accountId] = [
            'config' => $config,
            'worker' => new \stdClass,
            'wrapper' => new \stdClass,
        ];

        $property->setValue($command, $entries);
    }

    /**
     * @param  array<int, array<string, mixed>>  $desired
     * @param  list<int>  $seatedIds
     * @return array{seat: list<array<string, mixed>>, stop: list<int>}
     */
    private function planReseat(DaemonCommand $command, array $desired, array $seatedIds): array
    {
        $method = new ReflectionMethod(DaemonCommand::class, 'planReseat');
        $method->setAccessible(true);

        return $method->invoke($command, $desired, $seatedIds);
    }
}
