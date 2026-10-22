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
     * `$options` may carry `'maxlen' => int` to trim the stream to roughly
     * that many entries. An approximate (`MAXLEN ~`) trim is O(1) on the
     * server; without it a stream that only ever grows is a memory leak with
     * a production incident attached.
     *
     * @param  array<string, string>  $fields
     * @param  array<string, int>  $options
     * @return string The entry id actually stored.
     */
    public function xadd(string $stream, string $id, array $fields, array $options = []): string;

    /**
     * Pending-entry summary for a group, used to find entries whose consumer
     * died before acking.
     *
     * @param  string|null  $start  inclusive start, null for the beginning
     * @param  string|null  $end  exclusive end, null for the present
     * @return array<int, mixed> Driver-shaped; the consumer normalises.
     */
    public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array;

    /**
     * Claim stale pending entries from any consumer of the group.
     *
     * @return array<int, mixed> Driver-shaped `[nextId, entries, deletedIds]`
     *                           (predis RESP2) or an associative equivalent.
     */
    public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array;

    /**
     * Entries in an id range. Used to read a dead-letter stream back.
     *
     * @return array<int|string, array<string, string>> Entry id => fields.
     */
    public function xrange(string $stream, string $start, string $end, int $count = 100): array;

    /**
     * Entries in an id range, NEWEST FIRST — XREVRANGE.
     *
     * Distinct from reversing an xrange() result: xrange is bounded by COUNT
     * before you see it, so reversing its slice only gives you the newest of
     * the OLDEST N. An operator triaging a live incident needs the newest.
     *
     * @return array<int|string, array<string, string>> Entry id => fields.
     */
    public function xrevrange(string $stream, string $end, string $start, int $count = 100): array;

    /**
     * Remove entries from a stream, so a dead-letter replay can consume what
     * it replayed.
     *
     * @param  list<string>  $ids
     * @return int Number of entries removed.
     */
    public function xdel(string $stream, array $ids): int;

    /**
     * @return int Number of hash fields that existed and were removed.
     */
    public function hdel(string $key, string ...$fields): int;

    /**
     * Run a raw Redis command. Needed for the one operation that has no
     * dedicated method on either driver: XGROUP CREATE.
     *
     * @param  list<mixed>  $arguments
     */
    public function command(string $command, array $arguments = []): mixed;

    /**
     * Read from a consumer group. `>` returns never-delivered entries;
     * any other id (e.g. `0`) returns this consumer's pending entries.
     *
     * @param  array<string, int<0, max>>  $streams  Map of stream name => max entry count.
     * @return array<string, array<string, array<string, string>>> Stream name => (entry id => fields).
     */
    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array;

    /**
     * Acknowledge entries for a group.
     *
     * @param  list<string>  $ids
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
     * This is the LOOPBACK shape only the in-memory ArrayRedis double can
     * honor. The production adapter refuses it: a real SUBSCRIBE blocks.
     * A daemon that must listen without stopping needs the non-blocking
     * `psubscribe(array $channels, callable $onMessage): object` (handle
     * exposing only `stop(): void`) — declared on LaravelRedisAdapter,
     * NOT here, because three test doubles in files this change may not
     * touch implement this interface and an added abstract method makes
     * every one of them fatal.
     *
     * @param  callable(string, string): void  $handler
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

    /**
     * @return string|null The value, or null when the field does not exist.
     */
    public function hget(string $key, string $field): ?string;

    /**
     * Set a time-to-live on a key (seconds). Returns true on success.
     */
    public function expire(string $key, int $seconds): bool;
}
