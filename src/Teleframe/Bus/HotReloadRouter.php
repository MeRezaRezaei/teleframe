<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Closure;

/**
 * Applies RouteTable changes live (plan Task 2): route publishers call
 * `set()` and then announce on StreamSchema::RELOAD_CHANNEL; every
 * running process with a listening router re-reads the table without a
 * restart and fires the constructor's onReload callback with the fresh
 * map.
 *
 * Two consumption styles over the same cache:
 *  - push: `listen()` subscribes to the reload channel (blocking in a
 *    real runtime; the test double echoes publishes synchronously) and
 *    fires onReload(freshTable) on every ping;
 *  - poll: `refresh()` re-reads and returns the table for consumers
 *    that prefer checking between batches.
 */
final class HotReloadRouter
{
    private readonly Closure $onReload;

    /** @var array<string, string> pattern => target stream name */
    private array $routes;

    /**
     * @param callable(array<string, string>): void $onReload Fired with the fresh table on every reload signal
     */
    public function __construct(
        private readonly RedisConnectionContract $redis,
        callable $onReload,
    ) {
        $this->onReload = $onReload(...);

        $this->routes = (new RouteTable($this->redis))->all();
    }

    /**
     * Subscribe to the reload channel; every published ping re-reads
     * the route table and fires onReload with the fresh map. The
     * payload itself carries no data — any message means "re-read".
     */
    public function listen(): void
    {
        $this->redis->subscribe(
            StreamSchema::RELOAD_CHANNEL,
            function (string $channel, string $payload): void {
                ($this->onReload)($this->refresh());
            },
        );
    }

    /**
     * Re-read the route table into the local cache and return it (for
     * polling-based consumers; does not fire onReload).
     *
     * @return array<string, string> pattern => target stream name
     */
    public function refresh(): array
    {
        return $this->routes = (new RouteTable($this->redis))->all();
    }

    /**
     * The currently cached table (last constructor snapshot or refresh).
     *
     * @return array<string, string> pattern => target stream name
     */
    public function routes(): array
    {
        return $this->routes;
    }
}
