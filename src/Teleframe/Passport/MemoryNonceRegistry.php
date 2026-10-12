<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Passport;

/**
 * MemoryNonceRegistry (design §2/§9/§11, spec §5 G5): array-backed
 * NonceRegistry for tests, plain-PHP hosts, and the unconfigured module —
 * the NullLogger posture: constructible anywhere, zero container/facade
 * calls. Persistence is per-process by design; a host that needs nonces
 * to survive restart binds CacheNonceRegistry instead.
 *
 * Replay guard: consume() unsets the entry BEFORE returning, so a second
 * consume of the same nonce (a replayed webhook) hits unknown → null →
 * the orchestrator throws loud (design §11). Expiry is checked against
 * the injected clock seam so tests can fast-forward time: `$now` returns
 * the current unix timestamp (default `time()`).
 *
 * isAtomic() — TRUE, and here is the whole argument, without pretending it is
 * Redis's guarantee. The array is private to ONE process, and consume() does
 * no I/O: it reads a local array and unsets a key, with no point at which the
 * runtime can switch to another request (no syscall, no coroutine yield, no
 * await point), so no second consumer can observe a half-consumed nonce. The
 * two honest caveats, stated rather than glossed:
 *
 * - This is atomicity from LACK OF CONCURRENCY, not from a store-side
 *   indivisible operation. A future implementation that adds I/O to consume()
 *   would silently lose the guarantee, which is why the claim is documented
 *   here and the array stays the only state.
 * - The flip side of process-local state is that a nonce is invisible to every
 *   OTHER process: a second worker cannot double-consume it (it simply does not
 *   know it), and it also cannot consume it legitimately — which is why this
 *   registry is for tests / plain-PHP / unconfigured hosts, and a real host
 *   binds CacheNonceRegistry over a durable store.
 *
 * issue() uses random_bytes→bin2hex (128 bits of entropy — collision-
 * negligible, and deliberately NOT uniqid, which leaks time/sequence).
 * Zero regex — array keys only.
 */
final class MemoryNonceRegistry implements NonceRegistry
{
    /** @var array<string, array{userId: int, expiresAt: int}> */
    private array $nonces = [];

    private readonly int $ttl;

    /** @var \Closure(): int  the clock seam — returns the current unix timestamp */
    private readonly \Closure $now;

    /**
     * @param  int  $ttl  nonce lifetime in seconds (default 900 = 15 min)
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

    public function issue(int $userId): string
    {
        $nonce = bin2hex(random_bytes(16));
        $this->nonces[$nonce] = [
            'userId' => $userId,
            'expiresAt' => ($this->now)() + $this->ttl,
        ];

        return $nonce;
    }

    public function consume(string $nonce): ?int
    {
        $entry = $this->nonces[$nonce] ?? null;
        unset($this->nonces[$nonce]); // one-time: gone before any return

        if ($entry === null) {
            return null; // unknown or already consumed
        }

        if (($this->now)() >= $entry['expiresAt']) {
            return null; // expired
        }

        return $entry['userId'];
    }

    public function clear(string $nonce): void
    {
        unset($this->nonces[$nonce]);
    }
}
