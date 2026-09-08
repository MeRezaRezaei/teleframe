<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Illuminate\Redis\Connections\Connection as IlluminateConnection;
use Illuminate\Redis\Connections\PredisConnection;
use LogicException;

/**
 * Dumb passthrough from RedisConnectionContract to an illuminate/redis
 * connection (predis or phpredis under it). The two drivers disagree on
 * exactly three points, normalized here and verified against a live server:
 *
 * - Argument order: predis xadd(stream, fields, id) / xreadgroup(group,
 *   consumer, count, block, noack, ...keysAndIds) vs phpredis xAdd(stream,
 *   id, fields) / xReadGroup(group, consumer, [stream => id], count).
 * - xack ids: phpredis takes one array argument, predis's plain XACK
 *   command needs the ids spread.
 * - Response shape: phpredis already returns [stream => [id => [field =>
 *   value]]]; predis (RESP2) returns a list of [stream, [[id, [f1, v1,
 *   ...]], ...]] pairs with flat field lists.
 *
 * Everything else (hgetall/hset/publish/del) shares one signature on both
 * drivers and is forwarded untouched via Connection::command(). llen
 * dispatches on TYPE because LLEN alone throws WRONGTYPE on streams and
 * hashes. subscribe() is deliberately NOT implemented: the contract's
 * synchronous loopback-echo semantic only exists on the in-memory
 * ArrayRedis double; real pub/sub is blocking and belongs to a dedicated
 * daemon/router process, so we fail loudly instead of pretending.
 */
final class LaravelRedisAdapter implements RedisConnectionContract
{
    public function __construct(private readonly IlluminateConnection $connection)
    {
    }

    public function xadd(string $stream, string $id, array $fields): string
    {
        $result = $this->isPredis()
            ? $this->connection->command('xadd', [$stream, $fields, $id])
            : $this->connection->command('xadd', [$stream, $id, $fields]);

        return is_string($result) ? $result : '';
    }

    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
    {
        $count = $streams === [] ? 0 : max($streams);

        if ($this->isPredis()) {
            /** @var array<int, array{0: string, 1: array<int, array{0: string, 1: list<string>}>}>|null $raw */
            $raw = $this->connection->command(
                'xreadgroup',
                array_merge([$group, $consumer, $count, null, false], array_keys($streams), array_fill(0, count($streams), $minId)),
            );

            $result = [];
            foreach ($raw ?? [] as [$stream, $entries]) {
                foreach ($entries as [$entryId, $flat]) {
                    $result[$stream][$entryId] = self::pair($flat);
                }
            }

            return $result;
        }

        $keys = array_combine(array_keys($streams), array_fill(0, count($streams), $minId));

        /** @var array<string, array<string, array<string, string>>>|null $raw */
        $raw = $this->connection->command('xreadgroup', [$group, $consumer, $keys, $count]);

        return $raw ?? [];
    }

    public function xack(string $stream, string $group, array $ids): int
    {
        // phpredis xAck takes the id list as one array argument; predis's
        // XACK is a plain command that needs the ids spread.
        $result = $this->isPredis()
            ? $this->connection->command('xack', array_merge([$stream, $group], $ids))
            : $this->connection->command('xack', [$stream, $group, $ids]);

        return is_int($result) ? $result : (int) $result;
    }

    public function hgetall(string $key): array
    {
        $hash = $this->connection->command('hgetall', [$key]);

        return is_array($hash) ? $hash : [];
    }

    public function hset(string $key, string $field, string $value): int
    {
        return $this->toInt($this->connection->command('hset', [$key, $field, $value]));
    }

    public function publish(string $channel, string $payload): int
    {
        return $this->toInt($this->connection->command('publish', [$channel, $payload]));
    }

    public function subscribe(string $channel, callable $handler): void
    {
        unset($channel, $handler);

        throw new LogicException(
            'LaravelRedisAdapter cannot honor the synchronous loopback subscribe() semantic; ' .
            'bind the in-memory double in tests and use a dedicated pub/sub loop in daemons.',
        );
    }

    public function del(string ...$keys): int
    {
        return $this->toInt($this->connection->command('del', $keys));
    }

    public function llen(string $key): int
    {
        $type = $this->connection->command('type', [$key]);
        $kind = is_int($type) ? self::PHPREDIS_TYPES[$type] ?? 'none' : (string) $type;

        return match ($kind) {
            'stream' => $this->toInt($this->connection->command('xlen', [$key])),
            'hash' => $this->toInt($this->connection->command('hlen', [$key])),
            'list' => $this->toInt($this->connection->command('llen', [$key])),
            default => 0,
        };
    }

    private function isPredis(): bool
    {
        return $this->connection instanceof PredisConnection;
    }

    private function toInt(mixed $value): int
    {
        return is_int($value) ? $value : (int) $value;
    }

    /**
     * @param list<string> $flat
     * @return array<string, string>
     */
    private static function pair(array $flat): array
    {
        $paired = [];
        for ($i = 0, $n = count($flat); $i + 1 < $n; $i += 2) {
            $paired[$flat[$i]] = $flat[$i + 1];
        }

        return $paired;
    }

    /** phpredis TYPE_* int constants (extension defines them; keyed by value). */
    private const PHPREDIS_TYPES = [
        0 => 'none',
        1 => 'string',
        2 => 'set',
        3 => 'list',
        4 => 'zset',
        5 => 'hash',
        6 => 'stream',
    ];
}
