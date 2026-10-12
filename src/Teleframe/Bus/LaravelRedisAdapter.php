<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Bus;

use Illuminate\Redis\Connections\Connection as IlluminateConnection;
use Illuminate\Redis\Connections\PredisConnection;
use LogicException;
use RuntimeException;

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
 * psubscribe() (the NON-blocking contract the daemon's reload watcher
 * wants) is refused for the same reason, with a longer concrete
 * explanation — read its docblock before assuming a workaround.
 */
final class LaravelRedisAdapter implements RedisConnectionContract
{
    public function __construct(private readonly IlluminateConnection $connection) {}

    public function xadd(string $stream, string $id, array $fields, array $options = []): string
    {
        $maxlen = isset($options['maxlen']) ? (int) $options['maxlen'] : 0;

        if ($maxlen > 0) {
            // `~` is the approximate trim: O(1) on the server and it may keep a
            // few entries past the bound, which is the right trade for a stream
            // that is growing without limit. It goes right after the id on
            // both drivers; the field list follows.
            $result = $this->isPredis()
                ? $this->connection->command('xadd', [$stream, $fields, $id, 'MAXLEN', '~', $maxlen])
                : $this->connection->command('xadd', [$stream, $id, $fields, 'MAXLEN', '~', $maxlen]);
        } else {
            $result = $this->isPredis()
                ? $this->connection->command('xadd', [$stream, $fields, $id])
                : $this->connection->command('xadd', [$stream, $id, $fields]);
        }

        return is_string($result) ? $result : '';
    }

    /**
     * Raw command escape hatch. Needed for XGROUP CREATE, which has no
     * dedicated method exposed on both drivers.
     */
    public function command(string $command, array $arguments = []): mixed
    {
        return $this->connection->command($command, $arguments);
    }

    /**
     * Pending-entry summary. phpredis returns a flat summary array with a
     * 'message' key for consumers that are idle too long; that key is stripped
     * so the caller always gets the positional shape. The per-entry list
     * (id, consumer, idle-ms, delivery-count) is returned untouched, because
     * it is positional on both drivers and the consumer reads it by index.
     *
     * @return array<int, mixed>
     */
    public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
    {
        $arguments = [$stream, $group];
        if ($start !== null || $end !== null) {
            // XPENDING's extended form requires a range, and an empty
            // start/end pair is expressed as '-' (from the first id).
            $arguments[] = $start ?? '-';
            $arguments[] = $end ?? '+';
            $arguments[] = $count;
        }

        $result = $this->connection->command('xpending', $arguments);

        if (! is_array($result)) {
            return [];
        }

        // phpredis collapses the summary into an assoc array whose
        // 'message' slot holds either an idle-consumer count or an error
        // string. Positional access is what callers rely on, so normalise.
        if (array_key_exists('message', $result)) {
            unset($result['message']);
        }

        return array_values($result);
    }

    /**
     * Claim stale entries. Returned in the driver's own shape on purpose:
     * predis RESP2 answers [nextId, entries, deletedIds] while phpredis
     * answers an associative equivalent, and IngestConsumer::normalizeEntries()
     * already accepts both. Re-normalising here would make one driver's
     * correct reply look like the other's wrong one.
     *
     * @return array<int|string, mixed>
     */
    public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
    {
        // SIX positional arguments, and the literal 'COUNT' must NOT be one of
        // them. Predis\Command\Redis\XAUTOCLAIM::setArguments() splices the
        // FIRST FIVE and then — only if a sixth argument is present — pushes
        // its own 'COUNT' followed by $arguments[0]. Passing 'COUNT' here
        // therefore made $arguments[0] the literal string 'COUNT' and put
        // ["s","g","c1","0","0-0","COUNT","COUNT"] on the wire, which Redis
        // rejects — so crash-reclaim threw instead of reclaiming. Passing the
        // count itself as the sixth argument is what both drivers want:
        // predis expands it to `... COUNT <count>` and phpredis takes the
        // count as its sixth positional argument.
        $result = $this->connection->command(
            'xautoclaim',
            [$stream, $group, $consumer, (string) $minIdleMs, $start, $count],
        );

        return is_array($result) ? $result : [];
    }

    /**
     * @return array<int|string, array<string, string>>
     */
    public function xrange(string $stream, string $start, string $end, int $count = 100): array
    {
        $result = $this->connection->command('xrange', [$stream, $start, $end, 'COUNT', (string) $count]);

        return is_array($result) ? $result : [];
    }

    public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
    {
        // XREVRANGE takes its bounds in the OPPOSITE order to XRANGE: the
        // high id first, the low id second.
        $result = $this->connection->command('xrevrange', [$stream, $end, $start, 'COUNT', (string) $count]);

        return is_array($result) ? $result : [];
    }

    /**
     * @param  list<string>  $ids
     */
    public function xdel(string $stream, array $ids): int
    {
        if ($ids === []) {
            return 0;
        }

        $result = $this->isPredis()
            ? $this->connection->command('xdel', array_merge([$stream], $ids))
            : $this->connection->command('xdel', [$stream, $ids]);

        return $this->toInt($result);
    }

    public function hdel(string $key, string ...$fields): int
    {
        if ($fields === []) {
            return 0;
        }

        return $this->toInt($this->connection->command('hdel', array_merge([$key], $fields)));
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
                    $result[self::logicalName($stream, array_keys($streams))][$entryId] = self::pair($flat);
                }
            }

            return $result;
        }

        $keys = array_combine(array_keys($streams), array_fill(0, count($streams), $minId));

        /** @var array<string, array<string, array<string, string>>>|null $raw */
        $raw = $this->connection->command('xreadgroup', [$group, $consumer, $keys, $count]);

        $result = [];
        foreach ($raw ?? [] as $physical => $entries) {
            $result[self::logicalName($physical, array_keys($streams))] = $entries;
        }

        return $result;
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
            'LaravelRedisAdapter cannot honor the synchronous loopback subscribe() semantic; '.
            'bind the in-memory double in tests and use a dedicated pub/sub loop in daemons.',
        );
    }

    /**
     * Refused, loudly and on purpose. The non-blocking shape the contract
     * asks for is not constructible on the installed driver, and the
     * alternative — a loopback that "delivers" a message nobody received —
     * is exactly the decoration that made hot reload look alive.
     *
     * Why, concretely, with what is actually installed:
     *
     * - predis/predis ^3.6 is the ONLY driver here (no ext-redis:
     *   phpversion('redis') is false), so the connection is an
     *   Illuminate\Redis\Connections\PredisConnection.
     * - Illuminate\Redis\Connections\Connection::psubscribe($channels, $cb)
     *   forwards to PredisConnection::createSubscription(), which is
     *   `foreach ($this->pubSubLoop() as $message)` — an UNBOUNDED BLOCKING
     *   loop over Predis\Client::pubSubLoop(). It never returns, and it holds
     *   the connection for the whole time, so the shared RedisManager
     *   connection cannot then serve XADD/XREADGROUP. It is the same method
     *   subscribe() already refuses.
     * - Predis\Consumer\PubSub\Consumer::psubscribe() only WRITES the
     *   PSUBSCRIBE request; delivery happens in Consumer::next(), which
     *   blocks on the socket with no timeout. There is no non-blocking read.
     * - A dedicated SECOND connection alone does not fix it: the loop still
     *   never yields back to the caller, so the callback can only run while
     *   the caller is stuck inside psubscribe(). Delivering into this process
     *   while this process keeps working needs concurrency this PHP has not
     *   got — ext-parallel, ext-pthreads and ext-event are all absent
     *   (pcntl exists, but a forked child's mutations and its callbacks do
     *   not reach the parent's object graph; the only crossing is a
     *   socketpair the parent must poll, i.e. a pump point the
     *   `stop()`-only handle deliberately does not offer).
     *
     * What a production solution needs: a dedicated subscription runner —
     * a separate process (or forked runner) that owns its OWN predis
     * connection, blocks in a `pubSubLoop()` forever, and signals the
     * daemon out-of-band (SIGUSR1 via pcntl, or a marker the daemon's
     * existing per-rotation reseat already reads). Until that runner
     * exists, the daemon's per-rotation tf_accounts re-read remains the
     * real hot-reload path, and this throws so nothing pretends otherwise.
     */
    public function psubscribe(array $channels, callable $onMessage): object
    {
        unset($channels, $onMessage);

        throw new RuntimeException(
            'LaravelRedisAdapter::psubscribe() cannot be non-blocking on the installed driver: '
            .'predis/predis ^3.6 is the only driver (no ext-redis), and Illuminate\'s '
            .'PredisConnection::createSubscription() is an unbounded `foreach ($this->pubSubLoop() '
            .'as $message)` that monopolises the shared RedisManager connection and never returns, '
            .'while Predis\Consumer\PubSub\Consumer::next() has no non-blocking read. ext-parallel, '
            .'ext-pthreads and ext-event are not installed, and a handle exposing only stop() leaves '
            .'no pump point to deliver from a forked runner. Use a dedicated subscription runner '
            .'(own connection, blocking pubSubLoop) that signals the daemon, or bind the in-memory '
            .'ArrayRedis double in tests.',
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

    public function hget(string $key, string $field): ?string
    {
        $val = $this->connection->command('hget', [$key, $field]);

        return $val !== false ? (string) $val : null;
    }

    public function expire(string $key, int $seconds): bool
    {
        return (bool) $this->connection->command('expire', [$key, $seconds]);
    }

    private function isPredis(): bool
    {
        return $this->connection instanceof PredisConnection;
    }

    /**
     * Map a reply stream name back to the logical name the caller
     * requested. Laravel's redis connections usually apply a client-side
     * key prefix (e.g. "laravel-database-") and Redis echoes the PHYSICAL
     * name in XREADGROUP replies; callers look up StreamSchema::STREAM, so
     * replies must be keyed by the logical (unprefixed) name. The longest
     * prefix-strip that yields a requested name wins; with no prefix (or no
     * match) the physical name is kept — byte-stable for unprefixed setups.
     *
     * @param  string  $physical  stream name as echoed by Redis
     * @param  list<string>  $requested  logical names the caller asked for
     */
    private static function logicalName(string $physical, array $requested): string
    {
        $match = '';
        $longest = -1;

        foreach ($requested as $logical) {
            if ($logical !== '' && str_ends_with($physical, $logical) && strlen($logical) > $longest) {
                $match = $logical;
                $longest = strlen($logical);
            }
        }

        return $match !== '' ? $match : $physical;
    }

    private function toInt(mixed $value): int
    {
        return is_int($value) ? $value : (int) $value;
    }

    /**
     * @param  list<string>  $flat
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
