<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Store\ResilientCall;
use MeRezaRezaei\Teleframe\Tests\Support\SleepRecorder;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * ResilientCall (plan W2): the flood-wait / DC-migration pattern every
 * TStore wire call hangs on so long upload/download part loops survive
 * Telegram's two retry-shaped errors without an operator. Covers the
 * happy path, capped in-line flood sleeps (retry then succeed), the
 * over-cap deferral (rethrow, never sleep through it), migration swap
 * via the rebuild factory, no-factory rethrow, attempts exhaustion, and
 * passthrough of unrelated errors.
 */
final class ResilientCallTest extends TestCase
{
    public function test_passes_through_success_on_first_attempt(): void
    {
        $call = new ResilientCall(
            function (string $method, array $params): array {
                return ['_' => 'result', 'method' => $method, ...$params];
            },
        );

        $this->assertSame(
            ['_' => 'result', 'method' => 'ping', 'x' => 1],
            $call('ping', ['x' => 1]),
        );
    }

    public function test_flood_wait_sleeps_capped_seconds_then_retries(): void
    {
        $calls = 0;
        $sleep = new SleepRecorder;
        $call = new ResilientCall(
            function (string $method, array $params) use (&$calls): array {
                $calls++;
                if ($calls === 1) {
                    throw new FloodWaitException(7, 'FLOOD_WAIT_7', 420);
                }

                return ['_' => 'retried', 'calls' => $calls];
            },
            maxFloodWaitSeconds: 120,
            sleep: $sleep,
        );

        $this->assertSame(['_' => 'retried', 'calls' => 2], $call('ping'));
        $this->assertSame([7], $sleep->seconds);
        $this->assertSame(2, $calls);
    }

    public function test_flood_wait_above_cap_rethrows_without_sleeping(): void
    {
        $sleep = new SleepRecorder;
        $call = new ResilientCall(
            fn (): array => throw new FloodWaitException(600, 'FLOOD_WAIT_600', 420),
            maxFloodWaitSeconds: 120,
            sleep: $sleep,
        );

        try {
            $call('ping');
            $this->fail('expected RuntimeException for over-cap flood');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('defer', $e->getMessage());
            $this->assertStringContainsString('600s', $e->getMessage());
            $this->assertSame([], $sleep->seconds, 'must not sleep through an over-cap flood');
        }
    }

    public function test_dc_migration_swaps_call_via_rebuild_factory(): void
    {
        $migrated = false;
        $dcs = [];
        $rebuilt = fn (string $method, array $params): array => ['_' => 'on-dc', 'dc' => 2, ...$params];
        $call = new ResilientCall(
            function (string $method, array $params) use (&$migrated, &$rebuilt): array {
                if (! $migrated) {
                    $migrated = true;
                    throw new DcMigrationException(2, 'PHONE_MIGRATE_2');
                }

                return $rebuilt($method, $params);
            },
            function (int $dcId) use (&$dcs, &$rebuilt): callable {
                $dcs[] = $dcId;

                return $rebuilt;
            },
        );

        $this->assertSame(['_' => 'on-dc', 'dc' => 2], $call('ping'));
        $this->assertSame([2], $dcs, 'rebuild factory must receive the target dc');
    }

    public function test_dc_migration_without_rebuild_rethrows(): void
    {
        $call = new ResilientCall(
            fn (): array => throw new DcMigrationException(3),
        );

        $this->expectException(DcMigrationException::class);
        $call('ping');
    }

    public function test_exhausts_attempts_on_repeated_floods(): void
    {
        $calls = 0;
        $sleep = new SleepRecorder;
        $call = new ResilientCall(
            function () use (&$calls): array {
                $calls++;
                throw new FloodWaitException(1, 'FLOOD_WAIT_1', 420);
            },
            maxFloodWaitSeconds: 120,
            maxAttempts: 3,
            sleep: $sleep,
        );

        try {
            $call('ping');
            $this->fail('expected RuntimeException after exhausting attempts');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('exhausted', $e->getMessage());
            $this->assertSame(3, $calls);
            $this->assertSame([1, 1, 1], $sleep->seconds);
        }
    }

    public function test_unrelated_errors_propagate_untouched(): void
    {
        $call = new ResilientCall(
            fn (): array => throw new RuntimeException('boom'),
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('boom');
        $call('ping');
    }
}
