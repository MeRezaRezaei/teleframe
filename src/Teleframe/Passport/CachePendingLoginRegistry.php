<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

use Psr\SimpleCache\CacheInterface;

/**
 * CachePendingLoginRegistry — PSR-16-backed PendingLoginRegistry, riding the
 * same seam as {@see CacheNonceRegistry}: the host's own
 * `Psr\SimpleCache\CacheInterface` binding. Plain-PHP hosts inject an
 * in-memory store; Laravel hosts bind a durable one so a marker survives
 * between the request that started the trip and the request that finishes it
 * (which are minutes apart and routinely on different workers).
 *
 * WHAT IS STORED, precisely. Two digests and a TTL — never the passport
 * nonce, never a session id, never a decrypted field:
 *
 *   <prefix>pending.<token>  =>  ['sf' => sha256(session id), 'nd' => sha256(nonce)]
 *
 * The token in the key is 128 bits of `random_bytes`. The two values are
 * digests, so a store dump yields no usable session and no usable nonce. The
 * passport nonce exists in exactly two places at issue time — the
 * {@see PendingLoginTicket} the caller receives, and the Telegram request it
 * is put into — and after `issue()` returns, only the caller holds it.
 *
 * EXPIRY IS THE STORE'S, and a refused consume never resurrects the key. On an
 * {@see AtomicCacheStore} the single get-and-delete reports an expired entry
 * as absent and leaves it absent; on any other store the `delete` runs
 * unconditionally after the read, so an expired token stays expired and
 * cannot be revived by a later read.
 *
 * ATOMICITY mirrors {@see CacheNonceRegistry} and is reported rather than
 * assumed: a store implementing {@see AtomicCacheStore} is one indivisible
 * server-side GET+DEL, so of any number of overlapping callbacks carrying one
 * token at most one observes a binding; anything else is read-then-delete,
 * which refuses every sequential replay and leaves a narrow overlapping-call
 * window that `isAtomic() === false` names out loud.
 *
 * Zero regex — keys are string concatenation only.
 */
final class CachePendingLoginRegistry implements PendingLoginRegistry
{
    /**
     * @param  CacheInterface  $store  the PSR-16 store (durable in hosts)
     * @param  int  $ttl  marker lifetime in seconds
     * @param  string  $keyPrefix  key namespace, e.g. 'teleframe.passport.pending.'
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

    public function issue(string $sessionFingerprint): PendingLoginTicket
    {
        $token = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // ONE write carries both bindings and the TTL, so a marker is never
        // observable in a half-bound state.
        $this->store->set(
            $this->key($token),
            ['sf' => $sessionFingerprint, 'nd' => hash('sha256', $nonce)],
            $this->ttl,
        );

        return new PendingLoginTicket($token, $nonce);
    }

    public function consume(string $token): ?PendingLoginBinding
    {
        if ($token === '') {
            return null; // an empty handle is never a marker
        }

        $value = $this->store instanceof AtomicCacheStore
            // One round trip, indivisible: the first caller to be served takes
            // the marker, every other reads absent.
            ? $this->store->getAndDelete($this->key($token))
            // Fallback: read, then ALWAYS delete. A consumed, unknown or
            // expired token is never replayable. The residual overlapping-
            // call window is documented and reported by isAtomic() === false.
            : $this->readThenDelete($this->key($token));

        if (! is_array($value)) {
            return null;
        }

        $sf = $value['sf'] ?? null;
        $nd = $value['nd'] ?? null;

        if (! is_string($sf) || $sf === '' || ! is_string($nd) || $nd === '') {
            return null; // a malformed entry is a refusal, not a partial answer
        }

        return new PendingLoginBinding($sf, $nd);
    }

    public function clear(string $token): void
    {
        if ($token === '') {
            return;
        }

        $this->store->delete($this->key($token));
    }

    /** The non-atomic path, isolated: the delete runs before any return. */
    private function readThenDelete(string $key): mixed
    {
        $value = $this->store->get($key);
        $this->store->delete($key);

        return $value;
    }

    private function key(string $token): string
    {
        return $this->keyPrefix.$token;
    }
}
