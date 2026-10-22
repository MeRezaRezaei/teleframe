<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use Closure;
use DateInterval;
use DateTimeImmutable;
use Psr\SimpleCache\CacheInterface;

/**
 * Process-scoped PSR-16 store — the null-object default for the Q2d echo
 * elimination send registry (spec D2: cache seam, array default). A Laravel
 * host binds its own `Psr\SimpleCache\CacheInterface` (redis-backed) to
 * survive restarts; plain-PHP and tests use this in-memory default.
 *
 * TTLs are HONORED, per the PSR-16 contract: the third argument of `set()`
 * / `setMultiple()` is a lifetime, and a key past it is a miss for `get()`,
 * `getMultiple()` and `has()` even though it is still physically in the
 * array. That is what makes `ReplayDedup`'s cross-run window time-bounded —
 * this class is the SHIPPED DEFAULT behind `ReplayDedup` whenever the host
 * binds no PSR-16 cache, so an ignored TTL made the window span the whole
 * process. A `null` TTL means "no expiry" and never evicts.
 *
 * Expiry is lazy: a lapsed entry is dropped on first access (read, delete
 * or a write to the same key), which keeps `get()` O(1) and keeps the
 * registry from growing without bound on a long-running daemon.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    /**
     * Absolute UTC epoch second at which each key lapses; absent = no
     * expiry. Kept in a parallel map so a stored value keeps its exact
     * shape (no wrapper array leaking through `get()`).
     *
     * @var array<string, int>
     */
    private array $expires = [];

    /**
     * @param  callable(): int|null  $now  injectable clock (tests); defaults to `time()`
     */
    public function __construct(?callable $now = null)
    {
        $this->now = $now !== null ? Closure::fromCallable($now) : Closure::fromCallable('time');
    }

    /** @var callable(): int */
    private $now;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->forgetIfLapsed($key);

        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;
        $this->expires[$key] = $this->deadline($ttl);

        return true;
    }

    public function delete(string $key): bool
    {
        // PSR-16: delete MUST NOT raise for a missing key, and a lapsed key
        // is a miss — so it is removed here too and the documented `true`
        // is returned either way. Deleting a key never resurrects it.
        unset($this->values[$key], $this->expires[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];
        $this->expires = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $found = [];
        foreach ($keys as $key) {
            $found[$key] = $this->get((string) $key, $default);
        }

        return $found;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->forgetIfLapsed($key);

        return array_key_exists($key, $this->values);
    }

    /**
     * PSR-16 deadline for a TTL, as an absolute epoch second, or `null` for
     * "no expiry". An `int` is a lifetime in seconds added to now (a zero
     * or negative value therefore lapses immediately, which is the correct
     * reading of "expires in 0 seconds"). A `DateInterval` is added to now
     * so every shape works — including months and years, which have no
     * fixed length and only mean something relative to a date. The base
     * instant is pinned to the UTC epoch representation of now, so the
     * arithmetic is timezone- and DST-independent while still agreeing
     * with the `time()` the comparison uses.
     */
    private function deadline(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            return (new DateTimeImmutable('@'.($this->now)()))->add($ttl)->getTimestamp();
        }

        return ($this->now)() + $ttl;
    }

    /** Drop the key if its lifetime has passed; a no-op for immortal keys. */
    private function forgetIfLapsed(string $key): void
    {
        $expires = $this->expires[$key] ?? null;

        if ($expires !== null && $expires <= ($this->now)()) {
            unset($this->values[$key], $this->expires[$key]);
        }
    }
}
