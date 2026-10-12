<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Passport;

use DateInterval;
use MeRezaRezaei\Teleframe\Passport\AtomicCacheStore;

/**
 * FakeAtomicCacheStore — the atomic-store DOUBLE: a PSR-16 store that also
 * honours {@see AtomicCacheStore}, with no Redis anywhere.
 *
 * It exists to prove the SHAPE of the fix, not the wire: getAndDelete() is
 * the only method a consume is allowed to touch (call counters make an
 * accidental fall back to get()+delete() a test failure), and it refuses to
 * invent a key it does not hold — so "expired" is modelled as "absent" and a
 * consume cannot resurrect one. That is exactly the pair of promises
 * RedisAtomicCacheStore keeps against a live server.
 */
final class FakeAtomicCacheStore implements AtomicCacheStore
{
    /** @var array<string, mixed> */
    private array $values = [];

    public int $getAndDeleteCalls = 0;

    public int $getCalls = 0;

    public int $deleteCalls = 0;

    public int $setCalls = 0;

    public function getAndDelete(string $key): mixed
    {
        $this->getAndDeleteCalls++;
        $value = $this->values[$key] ?? null;
        unset($this->values[$key]);

        return $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->getCalls++;

        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->setCalls++;
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        $this->deleteCalls++;
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    /**
     * @param  iterable<string>  $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $found = [];
        foreach ($keys as $key) {
            $found[$key] = $this->values[$key] ?? $default;
        }

        return $found;
    }

    /**
     * @param  iterable<string, mixed>  $values
     */
    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param  iterable<string>  $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    /**
     * Model a server-side expiry: the key is GONE, the way Redis has already
     * dropped it. No tombstone, no leftover to be resurrected.
     */
    public function expire(string $key): void
    {
        unset($this->values[$key]);
    }
}
