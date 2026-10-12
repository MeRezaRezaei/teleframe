<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store;

use MeRezaRezaei\Teleframe\Core\Exceptions\DcMigrationException;
use MeRezaRezaei\Teleframe\Core\Exceptions\Rpc\FloodWaitException;
use RuntimeException;

/**
 * Resilient scope-call wrapper (verbatim S8/S11, plan W2): every TStore
 * wire call funnels through this so long upload/download loops survive
 * Telegram's two retry-shaped errors:
 *
 *  - FLOOD_WAIT_X  -> sleep capped at maxFloodWaitSeconds, then retry.
 *    A wait LONGER than the cap rethrows immediately — a worker must not
 *    sleep hours in-line on one call; the operator/queue owns that wait.
 *  - *_MIGRATE_X   -> rebuild the call for the target DC via the injected
 *    factory (FILE/PHONE/NETWORK/USER_MIGRATE are all 303 carry-on).
 *    Rebuilding swaps the underlying callable so subsequent calls in the
 *    same loop already land on the migrated DC.
 *
 * Attempts are bounded (maxAttempts per invocation). The caller supplies
 * the bare callable + an optional DC-rebuild factory; the sleep is
 * injectable so tests never actually sleep (defaults to real sleep()).
 *
 * The connection itself stays usable across RPC errors (see
 * MTProto\Client::call), so only FLOOD/MIGRATE are handled here —
 * everything else propagates untouched.
 */
final class ResilientCall
{
    /** @var callable(string, array<string, mixed>): array<string, mixed> */
    private $call;

    /** @var callable(int): callable|null */
    private $rebuild;

    /** @var callable(int): int */
    private $sleep;

    /**
     * @param  callable(string, array<string, mixed>): array<string, mixed>  $call
     * @param  callable(int): callable|null  $rebuild  (int $dcId) => new call callable for that DC; null = migrations rethrow
     * @param  callable(int): void|null  $sleep  injectable sleeper, default real sleep()
     */
    public function __construct(
        callable $call,
        ?callable $rebuild = null,
        private readonly int $maxFloodWaitSeconds = 120,
        private readonly int $maxAttempts = 3,
        ?callable $sleep = null,
    ) {
        $this->call = $call;
        $this->rebuild = $rebuild;
        $this->sleep = $sleep ?? static fn (int $seconds): int => sleep($seconds);
    }

    /**
     * Run one method with built-in flood/migration resilience.
     *
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     *
     * @throws FloodWaitException when the requested wait exceeds maxFloodWaitSeconds
     * @throws DcMigrationException when no rebuild factory is configured
     * @throws RuntimeException when floods/migrations exhaust maxAttempts
     */
    public function __invoke(string $method, array $params = []): array
    {
        for ($attempt = 1; $attempt <= $this->maxAttempts; $attempt++) {
            try {
                return ($this->call)($method, $params);
            } catch (FloodWaitException $e) {
                if ($e->seconds > $this->maxFloodWaitSeconds) {
                    throw new RuntimeException(
                        "{$method} flood requires waiting {$e->seconds}s, exceeding the {$this->maxFloodWaitSeconds}s in-line cap — defer, do not sleep through it",
                        0,
                        $e,
                    );
                }
                ($this->sleep)($e->seconds);
            } catch (DcMigrationException $e) {
                if ($this->rebuild === null) {
                    throw $e;
                }
                $this->call = ($this->rebuild)($e->getDcId());
            }
        }

        throw new RuntimeException(
            "{$method} exhausted {$this->maxAttempts} attempts on flood/migration retries",
        );
    }
}
