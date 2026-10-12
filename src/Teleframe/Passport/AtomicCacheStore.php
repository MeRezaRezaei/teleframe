<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\SimpleCache\CacheInterface;

/**
 * AtomicCacheStore — the ONE extra capability PSR-16 does not have, named
 * as a contract so a store can advertise it instead of being redis-sniffed.
 *
 * PSR-16 (CacheInterface) exposes get() and delete() as two separate round
 * trips and NOTHING else: no get-and-delete, no compare-and-set, no lock. A
 * one-time credential consumed through read-then-delete therefore has a
 * TOCTOU window — two callers can both observe the value before either
 * delete lands. That is fine for a cache and fatal for a replay guard.
 *
 * An implementor of THIS interface promises something PSR-16 cannot:
 * `getAndDelete($key)` performs the read and the removal as a single
 * indivisible server-side operation, so of any number of concurrent callers
 * at most ONE can ever observe the value. Redis GETDEL (or the equivalent
 * Lua GET+DEL script) is the reference implementation — see RedisAtomicCache.
 *
 * The other guarantee, which is a consequence and not a separate promise:
 * getAndDelete NEVER creates or refreshes the key. An expired entry is
 * reported as absent (null) and stays absent — the store's own expiry owns
 * the TTL, and a consume must not resurrect what it refused.
 *
 * Everything else in the surface is plain PSR-16, so an implementor is a
 * drop-in replacement: {@see CacheNonceRegistry} only ever calls getAndDelete
 * when the injected store advertises it, and never assumes it.
 */
interface AtomicCacheStore extends CacheInterface
{
    /**
     * Atomically read the value at $key and remove it in ONE indivisible
     * operation.
     *
     * Contract:
     * - at most one concurrent caller ever receives a non-null value;
     * - returns null when the key is absent OR expired (never both value and
     *   key, never a re-created key);
     * - must not create, refresh or extend the TTL of $key.
     */
    public function getAndDelete(string $key): mixed;
}
