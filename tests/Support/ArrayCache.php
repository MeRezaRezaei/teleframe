<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use Closure;
use DateInterval;
use DateTimeImmutable;
use MeRezaRezaei\Teleframe\Handler\InMemoryCache;
use Psr\SimpleCache\CacheInterface;

/**
 * Plain-array PSR-16 cache — the Q2d echo-elimination registry double in
 * plain-PHP tests and the standalone smoke. No Laravel, no redis.
 *
 * ## TTL IS HONOURED (2026-10-18) — this double used to discard it entirely
 *
 * `set()` accepted `null|int|DateInterval $ttl` and never read it:
 *
 *     $this->values[$key] = $value;      ← $ttl ignored
 *
 * Measured consequence: `set('k','v',1)`, sleep 2, `has('k')` was still `true`. A
 * TTL of **-5** was also ignored. So **22 test files** used a cache in which nothing
 * expires, and every one of them was green — including for reasons that had nothing to do
 * with the behaviour under test.
 *
 * The repository KNEW. `tests/Handler/ReplayDedupTest.php:145` says, in shipped source:
 *
 *     "PSR-16 store that actually honors TTLs (tick() advances the clock) — the in-memory
 *      defaults ignore TTL, so expiry is proven explicitly here."
 *
 * — and writes a FOURTH PSR-16 cache (`TtlArrayCache`) purely to escape this one. Four
 * PSR-16 doubles existed in this repository; three disagreed on TTL.
 *
 * ## WHY IT NOW MATCHES `InMemoryCache` RATHER THAN INVENTING ITS OWN RULE
 *
 * The semantics are `InMemoryCache`'s, and that is deliberate — it is the SHIPPED DEFAULT
 * behind `ReplayDedup` (`InMemoryCache.php:21`), so agreeing with it is what makes this
 * double usable for dedup-window tests at all:
 *
 *   - `null`            → no expiry. The documented way to say "forever".
 *   - `0` or negative   → a deadline at or before now, i.e. **immediately expired**.
 *     (`InMemoryCache` reads `$expires <= time()`; `FilesystemCache` disagreed here and was
 *     corrected in 46.73. This double now agrees with both.)
 *   - positive int      → expires at `now + ttl`, read lazily on `get()`/`has()`.
 *   - `DateInterval`    → converted the same way.
 *
 * **Tests that need to advance the clock deterministically still cannot use this class**
 * for sub-second precision — it reads the real clock, exactly as `InMemoryCache` does.
 * `TtlArrayCache`'s `tick()` remains the right tool for that, and it is why that class
 * still exists. What changed is that a TTL is no longer silently a no-op.
 *
 * @see InMemoryCache
 * @see tests/Handler/CacheDifferentialTest.php  (the lane that found the FilesystemCache half)
 */
final class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    /**
     * Deadline per key, or absent for no expiry.
     *
     * Kept in a parallel map rather than wrapped with the value, so `all()` keeps
     * returning exactly what a caller stored — the introspection tests depend on the
     * stored values being untouched.
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
        if ($this->isLapsed($key)) {
            $this->forgetIfLapsed($key);

            return $default;
        }

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
            $key = (string) $key;
            $found[$key] = $this->get($key, $default);
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
        if ($this->isLapsed($key)) {
            $this->forgetIfLapsed($key);

            return false;
        }

        return array_key_exists($key, $this->values);
    }

    /**
     * Has $key's deadline been reached?
     *
     * `<= time()` is `InMemoryCache`'s comparison (`InMemoryCache.php:140`) and it is the
     * defensible reading of PSR-16's "the time from now the entry expires": at the deadline
     * itself, it HAS expired.
     */
    private function isLapsed(string $key): bool
    {
        $deadline = $this->expires[$key] ?? null;

        return $deadline !== null && $deadline <= ($this->now)();
    }

    private function forgetIfLapsed(string $key): void
    {
        unset($this->values[$key], $this->expires[$key]);
    }

    /**
     * The deadline for a TTL: `null` means no expiry, and an already-passed time means
     * immediately lapsed.
     *
     * A TTL of 0 or less is NOT "no expiry" — `null` is, which is exactly why 0 cannot be
     * both. `InMemoryCache` expires it and so does this.
     */
    private function deadline(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if (is_int($ttl)) {
            return $ttl > 0 ? ($this->now)() + $ttl : ($this->now)();
        }

        $now = new DateTimeImmutable('@'.($this->now)());

        return max($now->add($ttl)->getTimestamp(), $now->getTimestamp());
    }

    /**
     * Every stored key => value. Test-only introspection (a store double has
     * no way to enumerate through the PSR-16 contract, and key-shape
     * assertions need it).
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        foreach (array_keys($this->values) as $key) {
            if ($this->isLapsed($key)) {
                $this->forgetIfLapsed($key);
            }
        }

        return $this->values;
    }
}
