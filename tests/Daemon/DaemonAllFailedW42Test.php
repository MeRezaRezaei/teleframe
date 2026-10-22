<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Daemon;

use MeRezaRezaei\Teleframe\Daemon\Daemon;
use MeRezaRezaei\Teleframe\Daemon\WorkerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/**
 * Pins the W-42 semantics of Daemon::allFailed(): empty input ⇒ false
 * (a no-seat roster must never flip the exit-1 give-up path), and the true
 * case is non-empty with EVERY account past the whole backoff ladder.
 */
final class DaemonAllFailedW42Test extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require dirname(__DIR__, 2).'/tests/Schema/FixesAutoload.php';
    }

    public function test_empty_roster_is_never_a_give_up(): void
    {
        self::assertFalse($this->allFailed([]));
    }

    public function test_not_every_account_past_the_ladder_is_not_all_failed(): void
    {
        $accounts = [
            1 => $this->account(failures: count(Daemon::BACKOFF_LADDER)),
            2 => $this->account(failures: count(Daemon::BACKOFF_LADDER) - 1),
        ];

        self::assertFalse($this->allFailed($accounts));
    }

    public function test_single_account_failing_alone_is_all_failed(): void
    {
        self::assertTrue($this->allFailed([1 => $this->account(failures: count(Daemon::BACKOFF_LADDER))]));
    }

    public function test_every_account_past_the_ladder_is_all_failed(): void
    {
        $accounts = [];
        foreach (range(1, 3) as $id) {
            $accounts[$id] = $this->account(failures: count(Daemon::BACKOFF_LADDER) + 1);
        }

        self::assertTrue($this->allFailed($accounts));
    }

    /**
     * @return array{worker: WorkerInterface, failures: int, isolated_until: int}
     */
    private function account(int $failures): array
    {
        return [
            'worker' => new CallbackNoopWorker,
            'failures' => $failures,
            'isolated_until' => 0,
        ];
    }

    private function allFailed(array $accounts): bool
    {
        $daemon = new Daemon([], static fn (): WorkerInterface => throw new \LogicException('never built'));
        $method = new ReflectionMethod(Daemon::class, 'allFailed');

        return $method->invoke($daemon, $accounts);
    }
}

final class CallbackNoopWorker implements WorkerInterface
{
    public function run(?callable $shouldStop = null): void {}
}
