<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler\Middleware;

use MeRezaRezaei\Teleframe\Handler\Update;
use Psr\SimpleCache\CacheInterface;

/**
 * Replay dedup, second middleware on the onion (spec 5a §3) — sits AFTER the
 * echo eliminator. Loop prevention catches our own replies; this drops a
 * re-ingested payload that the transport double-delivered within a TTL
 * window: the first dispatch marks ``updateId`` as seen, a second dispatch
 * of the same id short-circuits before any user handler runs.
 *
 * The TTL is constructor-injected (default 60s); plain-PHP hosts pass their
 * own value and a Laravel coordinator reads ``teleframe.handler.dedup_ttl``.
 * The ``onDrop`` callback lets the dispatcher count dropped replays for the
 * ops-facing ``seen_replays`` counter.
 */
final class ReplayDedup
{
    /** Cache key prefix for the seen registry. */
    public const KEY = 'teleframe.handler.seen';

    public function __construct(
        private readonly CacheInterface $seen,
        private readonly int $ttl = 60,
        private readonly ?\Closure $onDrop = null,
    ) {}

    /**
     * @param  callable(Update): mixed  $next
     */
    public function __invoke(Update $update, callable $next): mixed
    {
        // The event path (Update::fromMirror, source 'event') is NOT deduped.
        // Its array is a stored-truth projection, not a transport payload:
        // it carries only the constructor key, so its updateId degenerates to
        // a per-account+constructor constant and deduping it would mass-drop
        // every event dispatch after the first inside the TTL window (the
        // CRITICAL mass-drop the 2026-09-25 fix closed). That path rides
        // stored-truth PK idempotency instead.
        //
        // The guard is the SOURCE, not `$array === []` — since the
        // 2026-09-26 reachability fix the event path carries a non-empty
        // `['_'] => ctor` row so AppTrigger matches on real constructor
        // names, and the old emptiness test would have silently re-armed the
        // mass-drop.
        if ($update->source === 'event') {
            return $next($update);
        }

        $key = self::KEY.':'.$update->accountId.':'.$update->updateId();

        if ($this->seen->has($key)) {
            if ($this->onDrop !== null) {
                ($this->onDrop)();
            }

            return null;
        }

        $this->seen->set($key, true, $this->ttl);

        return $next($update);
    }
}
