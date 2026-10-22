<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Closure;
use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\RpcErrorException;
use MeRezaRezaei\Teleframe\Core\Exceptions\TelegramException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * RateLimitPolicy — Telegram's rate limits are HONOURED here, not counted.
 *
 * The gap this closes (V1.1's second deferred item). The notify core sent
 * through `ResilientCall`, which catches exactly ONE shape:
 * the MTProto-typed `FloodWaitException`. Neither of the other two shapes
 * the notify path actually meets was handled at all:
 *
 *  1. **Bot API 429.** `BotClient::call()` raises a plain
 *     `TelegramException` ("Telegram Bot API [429]: Too Many Requests:
 *     retry after 30") — not a `FloodWaitException`, so it flew straight
 *     through the old wrapper and out of the send.
 *  2. **`SLOWMODE_WAIT_x`.** `RpcExceptionResolver` has no typed case for
 *     it either, so it arrives as a generic `RpcErrorException` carrying the
 *     raw `rpcErrorMessage` — likewise unhandled.
 *
 * Both are rate limits the server told us about, with a number attached,
 * and neither was slept, capped, retried or surfaced. Counting them was
 * never the requirement; honouring them is.
 *
 * The policy, per invocation:
 *
 *  - the demanded wait is READ from the error (typed `FloodWaitException`
 *    → `$e->seconds`; `SLOWMODE_WAIT_x` / `FLOOD_WAIT_x` /
 *    `FLOOD_PREMIUM_WAIT_x` → sscanf + digit-strict tail, the same
 *    zero-regex parse `RpcExceptionResolver` uses; a Bot-API 429 →
 *    the `retry after N` of its description),
 *  - a wait WITHIN the in-line cap is SLEPT (through the injected sleeper)
 *    and the call is retried, up to `maxAttempts`,
 *  - a wait LONGER than the cap is NOT slept in-line — the call is deferred
 *    and the required seconds are stated in the thrown `NotifyException`,
 *    because a queue consumer must not sleep for hours inside one send
 *    (the `ResilientCall` posture this replaces, kept deliberately),
 *  - a rate limit whose seconds cannot be read is still ENFORCED: the
 *    bounded `unknownWaitSeconds` fallback is slept and the outcome says
 *    `known_wait = false`, so "we could not parse it" is visible instead
 *    of being rounded into "there was no limit",
 *  - every one of those facts lands in a `RateLimitOutcome`
 *    (`lastOutcome()`), which the sender puts on the send receipt and logs.
 *
 * Anything that is not a rate limit propagates untouched — logging and
 * classification never swallow (AGENTS.md: the engine always rethrows).
 * `DcMigrationException` keeps the old `ResilientCall` behaviour: rethrown
 * unless a rebuild factory is injected, in which case the callable is
 * swapped for the demanded DC and the attempt repeated.
 *
 * WHY A LOCAL SLEEPER SEAM: every wait goes through the injected
 * `callable(int): void`, defaulting to real `sleep()`. Tests therefore
 * assert the exact seconds honoured WITHOUT the wall clock, on every
 * branch (known wait, unreadable wait, over-cap deferral, exhaustion) —
 * a rate-limit test that slept for real would be a test about the
 * machine's patience, not about the policy.
 *
 * PLAIN-PHP CONSTRUCTIBLE: constructor wires callables and stores scalars;
 * nothing here touches a container, a network, an extension or a clock.
 *
 * HONEST LIMITS (what the tests do and do not prove):
 *  - The tests prove the POLICY: given a thrown error shaped like
 *    Telegram's, exactly the demanded number of seconds is slept, the
 *    retry really re-dials, the cap defers instead of sleeping, and the
 *    outcome reports the truth. They inject every one of those errors;
 *    none of them is a fixture captured from a live run.
 *  - What no test here can prove is that Telegram's real error text, real
 *    retry-after wording, or real flood lengths are exactly the shapes
 *    parsed above. The parse is deliberately permissive (uppercased
 *    prefix + digit-strict tail, `retry after N` anywhere in the message)
 *    so an unfamiliar spelling degrades to the bounded `unknownWaitSeconds`
 *    wait rather than to a silent pass-through — but a live Telegram run
 *    is the only authority on that, and this repo has never had one.
 */
final class RateLimitPolicy
{
    /** @var callable(string, array<string, mixed>): array<string, mixed> */
    private $call;

    /**
     * @var (Closure(int): (Closure(string, array<string, mixed>): array<string, mixed>))|null
     *   (int $dcId) => a new dialler for that DC; null = migrations rethrow
     */
    private readonly ?Closure $rebuild;

    /** @var Closure(int): void */
    private readonly Closure $sleep;

    /** The rate-limit prefixes Telegram uses on the MTProto wire, longest-first. */
    private const WIRE_PREFIXES = ['FLOOD_PREMIUM_WAIT_', 'FLOOD_WAIT_', 'SLOWMODE_WAIT_'];

    /** The Bot API's 429 status. */
    private const BOT_API_TOO_MANY_REQUESTS = 429;

    private ?RateLimitOutcome $outcome = null;

    /**
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $call  the transport dialler
     * @param  callable(int): (callable(string, array<string, mixed>): array<string, mixed>)|null  $rebuild  (int $dcId) => a new dialler for that DC; null = migrations rethrow
     * @param  int  $maxWaitSeconds  the in-line cap: the longest wait this policy will sleep
     * @param  int  $maxAttempts  total transport dials per invocation (1 = no retry)
     * @param  callable(int): void|null  $sleep  injectable sleeper, default real sleep()
     * @param  int  $unknownWaitSeconds  the bounded wait used when a rate limit arrives WITHOUT a readable number
     * @param  LoggerInterface|null  $logger  deferrals and honours are logged; the send still fails loudly
     */
    public function __construct(
        callable $call,
        ?callable $rebuild = null,
        private readonly int $maxWaitSeconds = 120,
        private readonly int $maxAttempts = 3,
        ?callable $sleep = null,
        private readonly int $unknownWaitSeconds = 30,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->call = $call;
        $this->rebuild = $rebuild === null ? null : Closure::fromCallable($rebuild);
        $this->sleep = $sleep === null
            ? static function (int $seconds): void {
                if ($seconds > 0) {
                    sleep($seconds);
                }
            }
        : Closure::fromCallable($sleep);
    }

    /**
     * Run one method, honouring every rate limit the server raises.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws NotifyException when a demanded wait exceeds the in-line cap, or the attempts are exhausted on rate limits
     * @throws Throwable ALWAYS for anything that is not a rate limit, and for a migration with no rebuild factory
     */
    public function __invoke(string $method, array $params = []): array
    {
        $attempts = 0;
        $waits = [];
        $knownWait = true;
        $migrated = false;
        $last = null;

        while ($attempts < max(1, $this->maxAttempts)) {
            $attempts++;

            try {
                $response = ($this->call)($method, $params);
                $this->outcome = new RateLimitOutcome($method, $attempts, $waits, $this->maxWaitSeconds, false, $knownWait);

                return $response;
            } catch (DcMigrationException $e) {
                $last = $e;

                if ($this->rebuild === null) {
                    $this->outcome = new RateLimitOutcome($method, $attempts, $waits, $this->maxWaitSeconds, false, $knownWait);

                    throw $e;
                }

                $this->call = ($this->rebuild)($e->getDcId());
                $migrated = true;
            } catch (Throwable $e) {
                $last = $e;

                if (! self::isRateLimit($e)) {
                    $this->outcome = new RateLimitOutcome($method, $attempts, $waits, $this->maxWaitSeconds, false, $knownWait);

                    throw $e;
                }

                $demanded = self::demandedWait($e);
                if ($demanded === null) {
                    // The server said "slow down" without a number we can
                    // read. Enforcement still applies: sleep the bounded
                    // fallback and record that the wait was not known.
                    $demanded = max(0, min($this->unknownWaitSeconds, $this->maxWaitSeconds));
                    $knownWait = false;
                }

                if ($demanded > $this->maxWaitSeconds) {
                    $this->outcome = new RateLimitOutcome($method, $attempts, $waits, $this->maxWaitSeconds, true, $knownWait);
                    $this->logger?->warning('teleframe.notify: rate limit deferred, wait exceeds the in-line cap', [
                        'method' => $method,
                        'required_seconds' => $demanded,
                        'cap_seconds' => $this->maxWaitSeconds,
                        'attempts' => $attempts,
                    ]);

                    throw new NotifyException(sprintf(
                        '%s rate limited: Telegram asked for %ds, over the %ds in-line cap — the wait is NOT honoured in-line, defer to the queue/operator (attempts %d, honoured %ds)',
                        $method,
                        $demanded,
                        $this->maxWaitSeconds,
                        $attempts,
                        array_sum($waits),
                    ), 0, $e);
                }

                $waits[] = $demanded;
                ($this->sleep)($demanded);
                // info, not notice: the policy is the transport-level fact
                // (method, wait, attempt). NotifyService::handle() raises the
                // operator-facing notice with the topic, so the same wait is
                // not reported twice at the same level.
                $this->logger?->info('teleframe.notify: rate limit honoured', [
                    'method' => $method,
                    'waited_seconds' => $demanded,
                    'attempt' => $attempts,
                    'known_wait' => $knownWait,
                ]);
            }
        }

        $reason = $migrated
            ? sprintf('%s exhausted %d attempts on DC-migration retries', $method, $attempts)
            : sprintf('%s exhausted %d attempts on rate limits (honoured %ds, cap %ds)', $method, $attempts, array_sum($waits), $this->maxWaitSeconds);

        $this->outcome = new RateLimitOutcome($method, $attempts, $waits, $this->maxWaitSeconds, true, $knownWait);

        throw new NotifyException($reason, 0, $last);
    }

    /** What the LAST invocation did — set on every exit path, including the throwing ones. */
    public function lastOutcome(): ?RateLimitOutcome
    {
        return $this->outcome;
    }

    /**
     * Whether $e is one of Telegram's rate limits, on EITHER transport:
     *
     *  - the MTProto-typed `FloodWaitException` (FLOOD_WAIT_x, FLOOD_PREMIUM_WAIT_x),
     *  - an `RpcErrorException` whose raw message starts with one of the wire
     *    rate-limit prefixes — `SLOWMODE_WAIT_x` and the untyped spellings,
     *  - a Bot API 429 (`TelegramException` with code 429, or the
     *    "[429]" the BotClient's message carries).
     *
     * Everything else is a plain failure and propagates untouched.
     */
    public static function isRateLimit(Throwable $e): bool
    {
        if ($e instanceof FloodWaitException) {
            return true;
        }

        if ($e instanceof RpcErrorException) {
            return self::prefixedSeconds($e->rpcErrorMessage) !== null;
        }

        if ($e instanceof TelegramException && $e->getCode() === self::BOT_API_TOO_MANY_REQUESTS) {
            return true;
        }

        return stripos($e->getMessage(), '['.self::BOT_API_TOO_MANY_REQUESTS.']') !== false
            || stripos($e->getMessage(), 'too many requests') !== false;
    }

    /**
     * The seconds Telegram demanded, or null when the error is a rate limit
     * whose number cannot be read (a 429 with no `retry after`, say). The
     * policy turns that null into a bounded wait rather than a free pass.
     */
    public static function demandedWait(Throwable $e): ?int
    {
        if ($e instanceof FloodWaitException) {
            return $e->seconds;
        }

        if ($e instanceof RpcErrorException) {
            return self::prefixedSeconds($e->rpcErrorMessage);
        }

        return self::retryAfterSeconds($e->getMessage());
    }

    /**
     * `FLOOD_WAIT_30` / `SLOWMODE_WAIT_17` / `FLOOD_PREMIUM_WAIT_600` → 30.
     *
     * Zero regex: an uppercased `str_starts_with` for the prefix, then a
     * `strspn` digit-strict tail (so `..._-5` and `..._ 5` are rejected —
     * `sscanf`'s `%d` alone would admit both) and exactly one `sscanf`
     * conversion consumed, which also rejects a trailing suffix.
     */
    private static function prefixedSeconds(string $message): ?int
    {
        $upper = strtoupper(trim($message));

        foreach (self::WIRE_PREFIXES as $prefix) {
            if (! str_starts_with($upper, $prefix)) {
                continue;
            }

            $tail = substr($upper, strlen($prefix));
            if ($tail === '' || strspn($tail, '0123456789') !== strlen($tail)) {
                continue;
            }

            $seconds = 0;
            $extra = "\0";
            if (sscanf($upper, $prefix.'%d%c', $seconds, $extra) === 1) {
                return $seconds;
            }
        }

        return null;
    }

    /**
     * The Bot API's 429 description spells the wait as "retry after 30"
     * (with or without a trailing period). Located case-insensitively
     * anywhere in the message, then read digit-strict, so a reworded
     * description still yields a number or yields null — never a guess.
     */
    private static function retryAfterSeconds(string $message): ?int
    {
        $needle = 'retry after ';
        $at = stripos($message, $needle);
        if ($at === false) {
            return null;
        }

        $seconds = 0;
        $extra = "\0";

        return sscanf(substr($message, $at + strlen($needle)), '%d%c', $seconds, $extra) === 1 ? $seconds : null;
    }
}
