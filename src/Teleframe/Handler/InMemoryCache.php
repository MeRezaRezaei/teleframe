<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Handler;

use DateInterval;
use Psr\SimpleCache\CacheInterface;

/**
 * Process-scoped PSR-16 store — the null-object default for the Q2d echo
 * elimination send registry (spec D2: cache seam, array default). A Laravel
 * host binds its own `Psr\SimpleCache\CacheInterface` (redis-backed) to
 * survive restarts; plain-PHP and tests use this in-memory default.
 */
final class InMemoryCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $values = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->values[$key] ?? $default;
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->values[$key] = $value;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->values[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->values = [];

        return true;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $found = [];
        foreach ($keys as $key) {
            $found[$key] = $this->values[$key] ?? $default;
        }

        return $found;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->values[(string) $key] = $value;
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            unset($this->values[(string) $key]);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }
}