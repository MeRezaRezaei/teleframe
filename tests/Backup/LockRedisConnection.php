<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Backup;

use Illuminate\Redis\Connections\PhpRedisConnection;

/**
 * In-memory phpredis connection fake for the per-set backup lock tests.
 *
 * BackupRunner/Pruner issue raw Redis through the Laravel cache store's
 * client: `SET key token EX ttl NX` to acquire, a Lua compare-and-del
 * (via `eval`) to release. This fake subclasses PhpRedisConnection and
 * overrides command() so the exact wire shapes Laravel forwards get
 * emulated without a server:
 *
 *   set  → command('set', [$key, $value, ['NX', 'EX' => ttl]])
 *          returns true (acquired) or false (held by another holder)
 *   eval → command('eval', [$script, [$key, $token], $numKeys])
 *          compare-and-delete: 1 on removal, 0 when the token is stale
 *
 * The store also records every call and every TTL so tests can assert
 * the lock key, the 256-bit token and the 4h expiration were used.
 */
final class LockRedisConnection extends PhpRedisConnection
{
    /** @var array<string, string> key → current value */
    private array $store = [];

    /** @var array<string, int> key → TTL in seconds applied at set time */
    private array $ttls = [];

    /** @var list<array{0: string, 1: array}> every captured command() call */
    private array $calls = [];

    public function __construct()
    {
        parent::__construct(new \stdClass);
    }

    /**
     * @param  array<mixed>  $parameters
     */
    public function command($method, array $parameters = [])
    {
        $method = strtolower((string) $method);
        $this->calls[] = [$method, $parameters];

        return match ($method) {
            'set' => $this->handleSet($parameters),
            'get' => $this->store[(string) ($parameters[0] ?? '')] ?? false,
            'del' => $this->handleDel($parameters),
            'eval' => $this->handleEval($parameters),
            default => false,
        };
    }

    /** Seed a held lock (as if another process owned it). */
    public function primeLock(string $key, string $token, int $ttl = 3600): void
    {
        $this->store[$key] = $token;
        $this->ttls[$key] = $ttl;
    }

    /** Current value under the key, null when absent. */
    public function lockValue(string $key): ?string
    {
        return $this->store[$key] ?? null;
    }

    /** TTL recorded when the key was last set, 0 when absent. */
    public function lockTtl(string $key): int
    {
        return $this->ttls[$key] ?? 0;
    }

    /**
     * @return list<array{0: string, 1: array}>
     */
    public function calls(): array
    {
        return $this->calls;
    }

    /**
     * SET key value [NX] [EX ttl] — NX fails when the key already exists.
     *
     * @param  array<mixed>  $parameters
     */
    private function handleSet(array $parameters): bool
    {
        $key = (string) ($parameters[0] ?? '');
        $value = (string) ($parameters[1] ?? '');
        $options = is_array($parameters[2] ?? null) ? $parameters[2] : [];

        $nx = in_array('NX', $options, true);
        if ($nx && array_key_exists($key, $this->store)) {
            return false;
        }

        $this->store[$key] = $value;
        $expire = $options['EX'] ?? null;
        if (is_int($expire) || is_string($expire)) {
            $this->ttls[$key] = (int) $expire;
        }

        return true;
    }

    /**
     * @param  array<mixed>  $parameters
     */
    private function handleDel(array $parameters): int
    {
        $removed = 0;
        $keys = is_array($parameters[0] ?? null) ? $parameters[0] : [$parameters[0] ?? null];
        foreach ($keys as $key) {
            $key = (string) $key;
            if (array_key_exists($key, $this->store)) {
                unset($this->store[$key], $this->ttls[$key]);
                $removed++;
            }
        }

        return $removed;
    }

    /**
     * Lua compare-and-del script emulation: KEYS[1] holds the token →
     * DEL and return 1; stale/absent → 0. Wire shape forwarded by
     * PhpRedisConnection::eval: [$script, [$key, $token], $numKeys].
     *
     * @param  array<mixed>  $parameters
     */
    private function handleEval(array $parameters): int
    {
        $arguments = is_array($parameters[1] ?? null) ? $parameters[1] : [];
        $numKeys = (int) ($parameters[2] ?? 0);

        $keys = array_slice($arguments, 0, $numKeys);
        $argv = array_slice($arguments, $numKeys);

        $key = (string) ($keys[0] ?? '');
        $token = (string) ($argv[0] ?? '');

        if (($this->store[$key] ?? null) === $token && $token !== '') {
            unset($this->store[$key], $this->ttls[$key]);

            return 1;
        }

        return 0;
    }
}
