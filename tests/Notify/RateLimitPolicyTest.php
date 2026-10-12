<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Notify;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use MeRezaRezaei\Teleframe\Notify\NotifyException;
use MeRezaRezaei\Teleframe\Notify\RateLimitPolicy;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Rate-limit ENFORCEMENT (V1.1's second deferred item).
 *
 * The waiter is an injected seam (`callable(int): void`) that RECORDS the
 * seconds it was asked to sleep, so the assertions are about the policy's
 * arithmetic and not about the machine's patience — a rate-limit test that
 * really slept 30 seconds would prove nothing except that the suite is
 * slow, and on CI it would be a flaky test about a loaded runner. The
 * errors the policy meets are CONSTRUCTED here (a real `FloodWaitException`,
 * a real `TelegramException` carrying the Bot API's own 429 wording), not
 * captured from a live run: what these tests prove is the POLICY, and the
 * PHPDoc says so.
 */
final class RateLimitPolicyTest extends TestCase
{
    /** @var list<int> the seconds the injected sleeper was asked to sleep */
    private array $slept = [];

    /** how many times the transport was actually dialled */
    private int $dials = 0;

    protected function setUp(): void
    {
        $this->slept = [];
        $this->dials = 0;
    }

    public function test_a_typed_flood_wait_is_honoured_exactly_and_the_call_is_retried(): void
    {
        $policy = $this->policy([$this->thrower(new FloodWaitException(30, 'FLOOD_WAIT_30', 420)), $this->reply(['_' => 'updates'])]);

        $response = $policy('sendMessage', ['chat_id' => 7]);

        self::assertSame(['_' => 'updates'], $response);
        self::assertSame([30], $this->slept, 'exactly the demanded seconds, not a rounded or invented number');
        self::assertSame(2, $this->dials, 'the retry really re-dialed the transport');

        $outcome = $policy->lastOutcome();
        self::assertNotNull($outcome);
        self::assertSame(2, $outcome->attempts);
        self::assertSame([30], $outcome->waits);
        self::assertSame(30, $outcome->waitedSeconds());
        self::assertSame(120, $outcome->capSeconds);
        self::assertFalse($outcome->deferred);
        self::assertTrue($outcome->knownWait);
        self::assertTrue($outcome->limited());
    }

    public function test_slowmode_wait_is_honoured_like_any_other_rate_limit(): void
    {
        // SLOWMODE_WAIT_x has no typed exception class in RpcExceptionResolver,
        // so it arrives as a generic RpcErrorException carrying the raw wire
        // message — the shape the old ResilientCall wrapper never caught.
        $policy = $this->policy([$this->thrower(new RpcErrorException('SLOWMODE_WAIT_17', 420)), $this->reply(['_' => 'updates'])]);

        self::assertSame(['_' => 'updates'], $policy('sendMessage', []));
        self::assertSame([17], $this->slept);
        self::assertSame(2, $this->dials);
    }

    public function test_a_bot_api_429_is_honoured_like_any_other_rate_limit(): void
    {
        // What BotClient::call() actually raises for a Bot API flood: a plain
        // TelegramException, NOT a FloodWaitException.
        $policy = $this->policy([$this->thrower(new TelegramException('Telegram Bot API [429]: Too Many Requests: retry after 12', 429)), $this->reply(['_' => 'updates'])]);

        self::assertSame(['_' => 'updates'], $policy('sendMessage', []));
        self::assertSame([12], $this->slept);
        self::assertSame(2, $this->dials);
    }

    public function test_a_429_without_a_readable_wait_still_waits_the_bounded_fallback(): void
    {
        $policy = $this->policy([$this->thrower(new TelegramException('Telegram Bot API [429]: Too Many Requests', 429)), $this->reply(['_' => 'updates'])], unknownWaitSeconds: 9);

        self::assertSame(['_' => 'updates'], $policy('sendMessage', []));
        self::assertSame([9], $this->slept, 'an unreadable wait is bounded, never treated as "no limit"');
        self::assertFalse($policy->lastOutcome()?->knownWait, 'and the record says the number was not known');
    }

    public function test_the_fallback_is_still_capped_by_the_in_line_cap(): void
    {
        $policy = $this->policy([$this->thrower(new TelegramException('Telegram Bot API [429]: Too Many Requests', 429)), $this->reply(['_' => 'updates'])], cap: 5, unknownWaitSeconds: 60);

        self::assertSame(['_' => 'updates'], $policy('sendMessage', []));
        self::assertSame([5], $this->slept);
    }

    public function test_a_wait_over_the_cap_is_deferred_loudly_and_never_slept(): void
    {
        $flood = new FloodWaitException(3600, 'FLOOD_WAIT_3600', 420);
        $policy = $this->policy([$this->thrower($flood), $this->reply(['never'])]);

        try {
            $policy('sendMessage', []);
            self::fail('a 3600s demand must not be slept in-line');
        } catch (NotifyException $e) {
            self::assertStringContainsString('3600s', $e->getMessage());
            self::assertStringContainsString('120s in-line cap', $e->getMessage());
            self::assertSame($flood, $e->getPrevious(), 'Telegram\'s own error is kept, not swallowed');
        }

        self::assertSame([], $this->slept, 'nothing was slept');
        self::assertSame(1, $this->dials, 'and nothing was retried');
        self::assertTrue($policy->lastOutcome()?->deferred);
        self::assertSame(0, $policy->lastOutcome()?->waitedSeconds());
    }

    public function test_repeated_rate_limits_are_exhausted_loudly_with_the_waits_recorded(): void
    {
        $policy = $this->policy([
            $this->thrower(new FloodWaitException(5, 'FLOOD_WAIT_5', 420)),
            $this->thrower(new FloodWaitException(5, 'FLOOD_WAIT_5', 420)),
            $this->reply(['never']),
        ], maxAttempts: 2);

        try {
            $policy('sendMessage', []);
            self::fail('two attempts must not become three');
        } catch (NotifyException $e) {
            self::assertStringContainsString('exhausted 2 attempts on rate limits', $e->getMessage());
        }

        self::assertSame([5, 5], $this->slept);
        self::assertSame(2, $this->dials);
        self::assertTrue($policy->lastOutcome()?->deferred);
        self::assertSame(10, $policy->lastOutcome()?->waitedSeconds());
    }

    public function test_a_single_attempt_policy_never_retries(): void
    {
        $policy = $this->policy([$this->thrower(new FloodWaitException(5, 'FLOOD_WAIT_5', 420))], maxAttempts: 1);

        $this->expectException(NotifyException::class);
        $policy('sendMessage', []);
    }

    public function test_a_failure_that_is_not_a_rate_limit_propagates_untouched(): void
    {
        $boom = new RuntimeException('chat not found');
        $policy = $this->policy([$this->thrower($boom)]);

        try {
            $policy('sendMessage', []);
            self::fail('a non-rate-limit failure must not be retried or rewritten');
        } catch (RuntimeException $e) {
            self::assertSame($boom, $e, 'the caller gets the original exception');
        }

        self::assertSame([], $this->slept);
        self::assertSame(1, $this->dials);
        self::assertFalse($policy->lastOutcome()?->limited());
    }

    public function test_a_dc_migration_rebuilds_the_dialler_and_retries(): void
    {
        $rebuilt = 0;
        $policy = new RateLimitPolicy(
            $this->dialler([$this->thrower(new DcMigrationException(2, 'USER_MIGRATE_2', 303))]),
            rebuild: function (int $dcId) use (&$rebuilt): callable {
                $rebuilt = $dcId;

                return fn (): array => ['_' => 'updates', 'dc' => $dcId];
            },
            sleep: $this->sleeper(),
        );

        self::assertSame(['_' => 'updates', 'dc' => 2], $policy('sendMessage', []));
        self::assertSame(2, $rebuilt);
        self::assertSame([], $this->slept);
    }

    public function test_a_dc_migration_without_a_rebuild_factory_rethrows(): void
    {
        $migration = new DcMigrationException(2, 'USER_MIGRATE_2', 303);
        $policy = $this->policy([$this->thrower($migration)]);

        try {
            $policy('sendMessage', []);
            self::fail('no rebuild factory means no silent retry');
        } catch (DcMigrationException $e) {
            self::assertSame($migration, $e);
        }
    }

    /**
     * The classification table, zero regex. The digit-strict tail matters:
     * `sscanf`'s `%d` alone would read `-5` and ` 5` out of a malformed
     * error and sleep a number Telegram never asked for.
     */
    public function test_rate_limit_classification(): void
    {
        self::assertTrue(RateLimitPolicy::isRateLimit(new FloodWaitException(30, 'FLOOD_WAIT_30', 420)));
        self::assertSame(30, RateLimitPolicy::demandedWait(new FloodWaitException(30, 'FLOOD_WAIT_30', 420)));

        $premium = new RpcErrorException('FLOOD_PREMIUM_WAIT_600', 420);
        self::assertTrue(RateLimitPolicy::isRateLimit($premium));
        self::assertSame(600, RateLimitPolicy::demandedWait($premium));

        $slowmode = new RpcErrorException('SLOWMODE_WAIT_17', 420);
        self::assertTrue(RateLimitPolicy::isRateLimit($slowmode));
        self::assertSame(17, RateLimitPolicy::demandedWait($slowmode));

        $bot = new TelegramException('Telegram Bot API [429]: Too Many Requests: retry after 12', 429);
        self::assertTrue(RateLimitPolicy::isRateLimit($bot));
        self::assertSame(12, RateLimitPolicy::demandedWait($bot));

        $unreadable = new TelegramException('Telegram Bot API [429]: Too Many Requests', 429);
        self::assertTrue(RateLimitPolicy::isRateLimit($unreadable));
        self::assertNull(RateLimitPolicy::demandedWait($unreadable), 'no number to honour, and none invented');

        foreach ([
            new RpcErrorException('FLOOD_WAIT_-5', 420),
            new RpcErrorException('FLOOD_WAIT_ 5', 420),
            new RpcErrorException('FLOOD_WAIT_', 420),
            new RpcErrorException('CHAT_WRITE_FORBIDDEN', 403),
            new RuntimeException('connection reset'),
        ] as $not) {
            self::assertFalse(RateLimitPolicy::isRateLimit($not), get_debug_type($not).': '.$not->getMessage());
        }
    }

    /**
     * @param  list<callable(): array<string, mixed>>  $script  one entry per attempt; the last repeats
     */
    private function policy(
        array $script,
        int $cap = 120,
        int $maxAttempts = 3,
        int $unknownWaitSeconds = 30,
    ): RateLimitPolicy {
        return new RateLimitPolicy(
            $this->dialler($script),
            null,
            $cap,
            $maxAttempts,
            $this->sleeper(),
            $unknownWaitSeconds,
        );
    }

    /**
     * @param  list<callable(): array<string, mixed>>  $script
     * @return callable(string, array<string, mixed>): array<string, mixed>
     */
    private function dialler(array $script): callable
    {
        $last = $script === [] ? null : $script[count($script) - 1];
        $cursor = 0;

        return function (string $method, array $params) use ($script, $last, &$cursor): array {
            $this->dials++;
            $step = $script[$cursor] ?? $last;
            $cursor++;

            if ($step === null) {
                return ['_' => 'ok'];
            }

            return $step();
        };
    }

    /** @return callable(int): void records the sleep, never performs one */
    private function sleeper(): callable
    {
        return function (int $seconds): void {
            $this->slept[] = $seconds;
        };
    }

    /** @return callable(): array<string, mixed> */
    private function reply(array $reply): callable
    {
        return static fn (): array => $reply;
    }

    /** @return callable(): array<string, mixed> */
    private function thrower(\Throwable $throwable): callable
    {
        return static function () use ($throwable): array {
            throw $throwable;
        };
    }
}
