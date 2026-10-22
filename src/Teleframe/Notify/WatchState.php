<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Notify;

use Psr\SimpleCache\CacheInterface;

/**
 * WatchState (design §2/G10, wave 2) — PSR-16-backed last-seen
 * bookkeeping for the gap watchdog. One timestamp per watched topic: the
 * "observed" heartbeat time (unix epoch) recorded by touch() and read by
 * lastSeen(). clear() forgets a topic's timestamp.
 *
 * The state knows ONLY what was observed — the expected arrival interval
 * lives in the registration (NoShowWatchdog), never here. The cache is
 * the package's established PSR-16 seam (Psr\SimpleCache\CacheInterface,
 * Teleframe.php:286-291): plain-PHP hosts inject InMemoryCache, Laravel
 * hosts bind a durable (redis-backed) store so last-seen survives the
 * process. Cache keys are opaque wire names under the constructable
 * prefix (teleframe.notify.watch.{topic}) — never renamed.
 *
 * Every send to a watched topic records the heartbeat (design §8: "each
 * send to topic backup calls WatchState::touch()"); the watchdog reads
 * the same state. Zero regex — keys are string concatenation only.
 */
final class WatchState
{
    /**
     * @param  CacheInterface  $cache  the PSR-16 store (durable in hosts)
     * @param  string  $prefix  key namespace for this watchdog's timestamps
     */
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly string $prefix = 'teleframe.notify.watch.',
    ) {}

    /** Record that the heartbeat for $topic was observed at unix time $at. */
    public function touch(string $topic, int $at): void
    {
        $this->cache->set($this->key($topic), $at);
    }

    /** The last observed heartbeat for $topic, or null when never seen. */
    public function lastSeen(string $topic): ?int
    {
        $value = $this->cache->get($this->key($topic));

        return is_int($value) ? $value : null;
    }

    /** Forget $topic's last-seen timestamp (grace period re-arms). */
    public function clear(string $topic): void
    {
        $this->cache->delete($this->key($topic));
    }

    private function key(string $topic): string
    {
        return $this->prefix.$topic;
    }
}
