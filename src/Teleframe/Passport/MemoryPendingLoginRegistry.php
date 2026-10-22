<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * MemoryPendingLoginRegistry — array-backed PendingLoginRegistry for tests,
 * plain-PHP hosts, and the unconfigured module (the NullLogger posture:
 * constructible anywhere, zero container/facade/config calls, persistence
 * per-process by design).
 *
 * The two honest caveats are stated rather than glossed, exactly as
 * {@see MemoryNonceRegistry} states its own:
 *
 *  - isAtomic() is TRUE from LACK OF CONCURRENCY, not from an indivisible
 *    store operation. consume() does no I/O — it reads a local array and
 *    unsets a key, with no syscall and no yield point — so no second consumer
 *    can observe a half-consumed marker. A future rewrite that adds I/O would
 *    silently lose the guarantee, which is why the claim lives here and the
 *    array stays the only state.
 *  - Process-local state means a marker is invisible to every OTHER worker: a
 *    second worker cannot double-consume it, and it cannot consume it
 *    legitimately either. That is why this is for tests / plain-PHP /
 *    unconfigured hosts, and why a real host binds the cache implementation.
 *
 * The clock seam exists so a test can fast-forward time and prove an EXPIRED
 * marker is refused, rather than asserting that expiry is a config value.
 *
 * Zero regex — array keys and string functions only.
 */
final class MemoryPendingLoginRegistry implements PendingLoginRegistry
{
    /** @var array<string, array{sf: string, nd: string, expiresAt: int}> */
    private array $markers = [];

    private readonly int $ttl;

    /** @var \Closure(): int */
    private readonly \Closure $now;

    /**
     * @param  int  $ttl  marker lifetime in seconds (default 900 = 15 min)
     * @param  \Closure(): int|null  $now  clock seam for tests (default time())
     */
    public function __construct(int $ttl = 900, ?\Closure $now = null)
    {
        $this->ttl = $ttl;
        $this->now = $now ?? static fn (): int => time();
    }

    public function isAtomic(): bool
    {
        return true; // see the class docblock: no I/O in consume(), no interleaving possible
    }

    public function issue(string $sessionFingerprint): PendingLoginTicket
    {
        $token = bin2hex(random_bytes(16));
        $nonce = bin2hex(random_bytes(16));

        // One indivisible write: there is no point at which a marker exists
        // without both bindings already recorded.
        $this->markers[$token] = [
            'sf' => $sessionFingerprint,
            'nd' => hash('sha256', $nonce),
            'expiresAt' => ($this->now)() + $this->ttl,
        ];

        return new PendingLoginTicket($token, $nonce);
    }

    public function consume(string $token): ?PendingLoginBinding
    {
        $entry = $this->markers[$token] ?? null;
        unset($this->markers[$token]); // one-time: gone before any return

        if ($entry === null) {
            return null; // unknown, or already spent by an earlier attempt
        }

        if (($this->now)() >= $entry['expiresAt']) {
            return null; // expired: the trip is over and cannot be revived
        }

        return new PendingLoginBinding($entry['sf'], $entry['nd']);
    }

    public function clear(string $token): void
    {
        unset($this->markers[$token]);
    }
}
