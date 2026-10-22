<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * PendingLoginRegistry — the pending-login marker store behind the ANONYMOUS
 * passport login path.
 *
 * WHY A SEPARATE REGISTRY AND NOT A REUSE OF {@see NonceRegistry}. The nonce
 * registry is `issue(int $userId) / consume(): ?int` because the authenticated
 * flow binds a login to a Laravel user, and an integer is the right shape for
 * that. An anonymous visitor has no user, so there is no integer to issue —
 * and the two obvious workarounds are both attacks: issue a fake id (the
 * callback's session check then compares a real session against a constant),
 * or make the id nullable (which is precisely how the first account takeover
 * in this module happened: a `?int $sessionUserId = null` whose null skipped
 * the comparison). The marker is a different THING — a login in flight — so
 * it gets its own type with its own one-time contract rather than being
 * squeezed into the user-shaped hole.
 *
 * THE CONTRACT, four properties, all load-bearing:
 *
 *  1. UNFORGEABLE. `issue()` mints the token with `random_bytes(16)` and never
 *     accepts a caller-supplied value, so no request can name a marker that
 *     was not minted here. (Deliberately not `uniqid()`, which leaks time and
 *     sequence.)
 *  2. BOUND AT ISSUE. The marker records the session fingerprint and the
 *     digest of the passport nonce in the same indivisible write, so there is
 *     no window in which a marker exists without both bindings.
 *  3. SINGLE-USE. `consume()` removes the entry before returning anything.
 *     A replayed callback finds nothing. A marker refused for one reason is
 *     spent by that attempt — a wrong browser cannot probe a token and leave
 *     it alive for the legitimate one.
 *  4. EXPIRING. Every entry carries a TTL owned by the store, so a marker
 *     that is never presented ages out instead of sitting in a cache forever.
 *
 * ATOMICITY is stated, not implied — the same two strengths
 * {@see NonceRegistry} documents, reported the same way by `isAtomic()`. A
 * store implementing {@see AtomicCacheStore} (Redis GETDEL) is indivisible, so
 * of any number of overlapping callbacks carrying one token at most ONE ever
 * observes a binding. Any other PSR-16 store is read-then-delete: every
 * SEQUENTIAL replay is still refused, two OVERLAPPING callbacks can both
 * observe the binding, and this registry says so out loud instead of guessing
 * which racer was first. `require_atomic_nonce` is honoured on this path too,
 * so a host that wants the hard guarantee gets a loud refusal rather than a
 * quiet downgrade.
 *
 * Implementations: {@see MemoryPendingLoginRegistry} (array, clock seam —
 * tests / plain-PHP / unconfigured) and {@see CachePendingLoginRegistry}
 * (PSR-16, the shipped durable seam).
 */
interface PendingLoginRegistry
{
    /**
     * Whether consume() is safe against CONCURRENT consumes of one token —
     * not merely safe against a sequential replay. See the class docblock.
     */
    public function isAtomic(): bool;

    /**
     * Mint a pending marker for a browser session and return the ticket the
     * host hands to it.
     *
     * @param  string  $sessionFingerprint  SessionFingerprint::of($sessionId)
     */
    public function issue(string $sessionFingerprint): PendingLoginTicket;

    /**
     * ONE-TIME consume: remove the marker, honour its TTL, and return what it
     * was bound to — or null when the token is unknown, already spent, or
     * expired. Null is the refusal; there is no partial answer.
     */
    public function consume(string $token): ?PendingLoginBinding;

    /** Forget a token without consuming it (host-cancelled / abandoned login). */
    public function clear(string $token): void;
}
