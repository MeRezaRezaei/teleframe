<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

/**
 * The minimal Redis surface the bus layer consumes (streams, one hash,
 * pub/sub, key deletion). Illuminate's RedisManager (predis or phpredis
 * under it) already satisfies every method signature below, so a future
 * adapter can wrap it without touching consumers; tests bind the in-memory
 * ArrayRedis double to this contract instead.
 */
interface RedisConnectionContract
{
    /**
     * Append an entry to a stream. Use id `*` for server-side auto ids.
     *
     * @param array<string, string> $fields
     * @return string The entry id actually stored.
     */
    public function xadd(string $stream, string $id, array $fields): string;

    /**
     * Read from a consumer group. `>` returns never-delivered entries;
     * any other id (e.g. `0`) returns this consumer's pending entries.
     *
     * @param array<string, int<0, max>> $streams Map of stream name => max entry count.
     * @return array<string, array<string, array<string, string>>> Stream name => (entry id => fields).
     */
    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array;

    /**
     * Acknowledge entries for a group.
     *
     * @param list<string> $ids
     * @return int Number of ids that were actually pending.
     */
    public function xack(string $stream, string $group, array $ids): int;

    /**
     * @return array<string, string> All field => value pairs of the hash.
     */
    public function hgetall(string $key): array;

    /**
     * @return int 1 when the field was created, 0 when it was overwritten.
     */
    public function hset(string $key, string $field, string $value): int;

    /**
     * Publish a payload to a channel.
     *
     * @return int Number of receivers that got the message.
     */
    public function publish(string $channel, string $payload): int;

    /**
     * Register a channel handler, invoked synchronously as
     * `handler(string $channel, string $payload)` on publish.
     *
     * @param callable(string, string): void $handler
     */
    public function subscribe(string $channel, callable $handler): void;

    /**
     * @return int Number of keys that existed and were removed.
     */
    public function del(string ...$keys): int;

    /**
     * Element count for the key (stream entry count, hash field count, ...).
     *
     * @return int<0, max>
     */
    public function llen(string $key): int;
}
