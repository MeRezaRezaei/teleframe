<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * NonceRegistry (design §2/§4/§9/§11, spec §5 G4): the replay-guard
 * lifecycle tying a Telegram Passport response to the web session that
 * started it. The host calls issue() when it asks the bot for passport
 * data (the nonce goes into the passport request, out of the package);
 * the login chain calls consume() with the nonce decrypted from the
 * webhook payload.
 *
 * REPLAY-GUARD CONTRACT (design §11): consume() is ONE-TIME and TTL'd —
 * a consume removes the nonce before returning, so a replayed webhook
 * (queue redelivery) hits an unknown nonce on its second delivery and
 * consume() returns null. The orchestrator turns that null into a loud
 * PassportLoginException ("unknown nonce") — never a second login, never
 * a silent drop. The host re-issues a fresh nonce for a new attempt.
 *
 * ATOMICITY, STATED PRECISELY (this is the part that was only a docblock
 * note before). "One-time" has two distinct strengths, and implementations
 * differ between them:
 *
 *  1. SEQUENTIAL single-use — a second consume of the same nonce, however
 *     long after the first, always returns null. EVERY implementation
 *     guarantees this unconditionally. It is a property of the code, not of
 *     the store.
 *  2. ATOMIC single-use under CONCURRENCY — of N simultaneous consumes, at
 *     most ONE observes a userId. This needs a store-side indivisible
 *     read-and-delete; PSR-16 alone cannot provide it.
 *
 * `isAtomic()` is the capability flag that tells a host which of the two it
 * is holding, at runtime, without reading this docblock:
 *
 * - true  — consume() cannot be raced: either the store implements
 *   {@see AtomicCacheStore} (Redis GETDEL / Lua GET+DEL), or the state is
 *   process-local and PHP cannot preempt mid-call (MemoryNonceRegistry).
 * - false — consume() is single-use but read-then-delete: two genuinely
 *   concurrent consumes of one nonce can BOTH observe the userId and both
 *   log in. Sequential replays are still refused. Hosts that cannot accept
 *   that window must either bind an atomic store, or set
 *   teleframe.passport.require_atomic_nonce so the module refuses to bind at
 *   all (loud, at resolve) rather than boot with a weaker guarantee than
 *   the host asked for.
 *
 * Implementations: MemoryNonceRegistry (array, borrowed time seam — tests
 * / plain-PHP / unconfigured) and CacheNonceRegistry (PSR-16 backed, the
 * shipped cache seam). Keys are opaque nonce strings: never renamed.
 */
interface NonceRegistry
{
    /**
     * Whether consume() is safe against CONCURRENT consumes of the same
     * nonce — not merely safe against a sequential replay.
     *
     * False does NOT mean the registry is broken or leaky in the common
     * case: a second consume after the first returned is always refused. It
     * means there is a narrow window in which two overlapping requests could
     * both pass. See the interface docblock and
     * {@see NonAtomicNonceStoreException}.
     */
    public function isAtomic(): bool;

    /**
     * Issue a fresh one-time nonce bound to $userId and return it.
     */
    public function issue(int $userId): string;

    /**
     * One-time consume: remove the nonce, honour its TTL, and return the
     * userId it was issued for — or null when the nonce is unknown,
     * already consumed, or expired.
     */
    public function consume(string $nonce): ?int;

    /**
     * Forget a nonce without consuming it (flush / host-cancelled login).
     */
    public function clear(string $nonce): void;
}
