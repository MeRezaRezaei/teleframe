<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\SimpleCache\CacheInterface;

/**
 * CacheNonceRegistry (design §2/§9/§11, spec §5 G4): PSR-16-backed
 * NonceRegistry riding the package's established cache seam
 * (Psr\SimpleCache\CacheInterface — the same seam Teleframe::send() and
 * the handler middleware require). Plain-PHP hosts inject InMemoryCache;
 * Laravel hosts bind a durable (redis-backed) store so issued nonces
 * survive the process between the web session that issued them and the
 * webhook that consumes them.
 *
 * Keys are opaque wire names under the constructable prefix
 * (`$keyPrefix.$nonce`) — never renamed. issue() stores the userId with
 * a TTL entry (the store owns expiry, so an expired consume reads the
 * key as absent → null).
 *
 * consume() ATOMICITY — CLOSED where the store allows it, and named where
 * it does not. Two strengths, both reported by isAtomic():
 *
 * - ATOMIC (store implements {@see AtomicCacheStore}, i.e. the module's
 *   RedisAtomicCacheStore over the host's cache): consume is ONE indivisible
 *   server-side GET+DEL, so of any number of simultaneous consumes of one
 *   nonce at most ONE observes a userId. A redelivered webhook loses the race
 *   and reads null — the orchestrator then throws loud "unknown nonce".
 *   Expiry is refused by the same operation and the key is never
 *   re-created, so a refused consume leaves no fresh TTL behind it.
 * - NON-ATOMIC (any other PSR-16 store): consume is get → delete → return.
 *   The delete ALWAYS runs, so every SEQUENTIAL replay is refused exactly as
 *   before, but PSR-16 exposes no atomic primitive, so two genuinely
 *   OVERLAPPING consumes can both observe the userId before either delete
 *   lands. Rather than hide that, the registry reports isAtomic() === false
 *   and PassportServiceProvider can refuse the bind outright when the host
 *   sets teleframe.passport.require_atomic_nonce
 *   (NonAtomicNonceStoreException). Note WHAT IS NOT DONE on this path: the
 *   registry does not try to pick a winner between two racers. Once both
 *   hold a value the store cannot say which was first, so refusing one
 *   arbitrarily would be a coin flip wearing a guarantee's clothes — the
 *   honest fix is a store that can decide, not a guess layered on top.
 *
 * Zero regex — keys are string concatenation only.
 */
final class CacheNonceRegistry implements NonceRegistry
{
    /**
     * @param  CacheInterface  $store  the PSR-16 store (durable in hosts)
     * @param  int  $ttl  nonce lifetime in seconds (teleframe.passport.nonce_ttl)
     * @param  string  $keyPrefix  key namespace, e.g. 'teleframe.passport.nonce.'
     */
    public function __construct(
        private readonly CacheInterface $store,
        private readonly int $ttl,
        private readonly string $keyPrefix,
    ) {}

    public function isAtomic(): bool
    {
        return $this->store instanceof AtomicCacheStore;
    }

    public function issue(int $userId): string
    {
        $nonce = bin2hex(random_bytes(16));
        $this->store->set($this->key($nonce), $userId, $this->ttl);

        return $nonce;
    }

    public function consume(string $nonce): ?int
    {
        $userId = $this->store instanceof AtomicCacheStore
            // One round trip, indivisible: whoever the server serves first
            // takes the nonce, every other caller reads absent. Never creates
            // or refreshes the key, so an expired nonce stays expired.
            ? $this->store->getAndDelete($this->key($nonce))
            // Fallback: read, then ALWAYS delete — a consumed OR unknown
            // nonce is never replayable. Documented read-then-delete window
            // on non-atomic stores (class docblock); isAtomic() === false
            // says so out loud.
            : $this->readThenDelete($this->key($nonce));

        return is_int($userId) ? $userId : null;
    }

    public function clear(string $nonce): void
    {
        $this->store->delete($this->key($nonce));
    }

    /**
     * The non-atomic path, isolated so the guarantee difference is visible at
     * a glance: the delete runs unconditionally, before any return.
     */
    private function readThenDelete(string $key): mixed
    {
        $value = $this->store->get($key);
        $this->store->delete($key);

        return $value;
    }

    private function key(string $nonce): string
    {
        return $this->keyPrefix.$nonce;
    }
}
