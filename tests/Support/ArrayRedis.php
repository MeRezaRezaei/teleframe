<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Support;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;

/**
 * In-memory double for the Redis subset the bus phase consumes — the test
 * substrate for StreamSchema/RedisStreamSink, the ingest consumer, routing
 * and daemon tasks. Deliberately Laravel-agnostic: a plain class satisfying
 * RedisConnectionContract, not an Illuminate RedisManager subclass.
 *
 * Group semantics model the real XREADGROUP subset the plan uses: `>`
 * delivers never-delivered entries exactly once per group, a concrete id
 * (e.g. `0`) re-reads entries still pending for that consumer, and xack
 * clears pending ids. subscribe() registers loopback handlers that
 * publish() invokes synchronously (echo channel — no daemon needed).
 */
final class ArrayRedis implements RedisConnectionContract
{
    /** @var array<string, array<string, array<string, string>>> */
    public array $streams = [];

    /** @var array<string, array<string, string>> */
    public array $hashes = [];

    /** @var array<string, array<string, array<string, string>>> group => stream => (entry id => consumer) */
    private array $pending = [];

    /** @var array<string, array<string, array<string, true>>> group => stream => ids ever delivered via `>`. Real XREADGROUP advances a group last-delivered-id independent of acks — acked entries must NOT reappear for `>`. */
    private array $delivered = [];

    /** @var array<string, array<string, true>> group => stream => exists, as created by XGROUP CREATE ... MKSTREAM */
    public array $groups = [];

    /** @var array<string, array<string, array<string, int>>> group => stream => (entry id => delivery ms) — the idle clock for xpending/xautoclaim. */
    private array $deliveredAt = [];

    /** @var array<string, list<callable(string, string): void>> */
    private array $subscribers = [];

    private int $lastMs = 0;

    private int $seq = -1;

    /** Test clock shift (ms) — warpTime() ages pending entries past the reclaim threshold. */
    private int $clockOffsetMs = 0;

    public function xadd(string $stream, string $id, array $fields, array $options = []): string
    {
        if ($id === '*') {
            $id = $this->nextId();
        } elseif (str_contains($id, '-')) {
            sscanf($id, '%d-%d', $ms, $seq);
            if (is_int($ms)) {
                $this->lastMs = $ms;
                $this->seq = is_int($seq) ? $seq : 0;
            }
        }

        $this->streams[$stream][$id] = $fields;

        $this->trim($stream, $options);

        return $id;
    }

    /**
     * The approximate (`~`) trim, modelled exactly. A real server may keep a
     * few entries past the bound; a test double that trimmed EXACTLY would
     * pass tests a real deployment fails, so this cuts the oldest entries and
     * stops — the observable property under test is "the stream is bounded",
     * not the precise entry that survives the boundary.
     *
     * @param  array<string, int>  $options
     */
    private function trim(string $stream, array $options): void
    {
        $maxlen = (int) ($options['maxlen'] ?? 0);

        if ($maxlen <= 0 || ! isset($this->streams[$stream])) {
            return;
        }

        $ids = array_keys($this->streams[$stream]);
        $excess = count($ids) - $maxlen;

        if ($excess <= 0) {
            return;
        }

        foreach (array_slice($ids, 0, $excess) as $stale) {
            unset($this->streams[$stream][$stale]);
        }
    }

    /**
     * Raw-command escape hatch. Only XGROUP CREATE is issued through it, and
     * the double records the group so xreadgroup can serve it — which is what
     * lets a test assert that a fresh consumer creates its own group.
     *
     * @param  list<mixed>  $arguments
     */
    public function command(string $command, array $arguments = []): mixed
    {
        if (strtolower($command) !== 'xgroup') {
            throw new \RuntimeException('ArrayRedis double does not implement '.$command);
        }

        // XGROUP CREATE <stream> <group> <id> [MKSTREAM] — note the leading
        // literal 'CREATE' is arguments[0]; the stream is arguments[1].
        $stream = (string) ($arguments[1] ?? '');
        $group = (string) ($arguments[2] ?? '');

        if ($stream === '' || $group === '') {
            throw new \RuntimeException('XGROUP CREATE needs a stream and a group');
        }

        if (isset($this->groups[$group][$stream])) {
            throw new \RuntimeException('BUSYGROUP Consumer Group name already exists');
        }

        // MKSTREAM: the stream exists from this moment on, even with no entries.
        $this->groups[$group][$stream] = true;
        $this->streams[$stream] ??= [];

        return 'OK';
    }

    public function hdel(string $key, string ...$fields): int
    {
        $removed = 0;

        foreach ($fields as $field) {
            if (array_key_exists($field, $this->hashes[$key] ?? [])) {
                unset($this->hashes[$key][$field]);
                $removed++;
            }
        }

        return $removed;
    }

    public function xreadgroup(string $group, string $consumer, array $streams, string $minId = '>'): array
    {
        $result = [];

        foreach ($streams as $stream => $count) {
            $entries = $this->streams[$stream] ?? [];
            $pending = $this->pending[$group][$stream] ?? [];

            if ($minId === '>') {
                $delivered = $this->delivered[$group][$stream] ?? [];
                $fresh = array_diff_key($entries, $delivered);
                $selected = array_slice($fresh, 0, $count, true);
                foreach ($selected as $entryId => $_fields) {
                    $this->pending[$group][$stream][$entryId] = $consumer;
                    $this->delivered[$group][$stream][$entryId] = true;
                    $this->deliveredAt[$group][$stream][$entryId] = $this->nowMs();
                }
            } else {
                $own = array_filter(
                    $pending,
                    static fn (string $owner): bool => $owner === $consumer,
                );
                $selected = array_slice(array_intersect_key($entries, $own), 0, $count, true);
            }

            if ($selected !== []) {
                $result[$stream] = $selected;
            }
        }

        return $result;
    }

    public function xack(string $stream, string $group, array $ids): int
    {
        $acked = 0;

        foreach ($ids as $id) {
            if (isset($this->pending[$group][$stream][$id])) {
                unset($this->pending[$group][$stream][$id]);
                $acked++;
            }
        }

        return $acked;
    }

    /** Number of hgetall() calls served — lets a test assert the route-table read cost, not just its result. */
    public int $hgetallCalls = 0;

    public function hgetall(string $key): array
    {
        $this->hgetallCalls++;

        return $this->hashes[$key] ?? [];
    }

    public function hset(string $key, string $field, string $value): int
    {
        $existed = array_key_exists($field, $this->hashes[$key] ?? []);
        $this->hashes[$key][$field] = $value;

        return $existed ? 0 : 1;
    }

    public function publish(string $channel, string $payload): int
    {
        $handlers = $this->subscribers[$channel] ?? [];
        foreach ($handlers as $handler) {
            $handler($channel, $payload);
        }

        return count($handlers);
    }

    public function subscribe(string $channel, callable $handler): void
    {
        $this->subscribers[$channel][] = $handler;
    }

    public function del(string ...$keys): int
    {
        $deleted = 0;

        foreach ($keys as $key) {
            foreach ([$this->streams, $this->hashes] as $store) {
                if (array_key_exists($key, $store)) {
                    $deleted++;
                }
            }
            unset($this->streams[$key], $this->hashes[$key]);
        }

        return $deleted;
    }

    public function llen(string $key): int
    {
        if (isset($this->streams[$key])) {
            return count($this->streams[$key]);
        }
        if (isset($this->hashes[$key])) {
            return count($this->hashes[$key]);
        }

        return 0;
    }

    public function hget(string $key, string $field): ?string
    {
        return $this->hashes[$key][$field] ?? null;
    }

    public function expire(string $key, int $seconds): bool
    {
        // No-op in-memory: TTL semantics are not modeled in the double.
        // Keys live until explicitly deleted via del().
        return array_key_exists($key, $this->streams) || array_key_exists($key, $this->hashes);
    }

    /**
     * XPENDING, in the REAL reply shape.
     *
     * The summary form (`XPENDING <stream> <group>`) answers
     *
     *     [count, minId, maxId, [[consumer, count], ...]]
     *
     * and the extended form (a range was asked for) answers a bare list of
     *
     *     [id, consumer, idleMs, deliveryCount]
     *
     * rows with no count slot at all. Both are modelled here.
     *
     * This double used to fabricate a phpredis-ASSUMED array — one
     * ['name' => ..., 'pending' => ...] row per consumer plus a final
     * ['name' => 'total', 'pending' => N] row — which NO driver produces. That
     * fiction is exactly why IngestConsumer::pendingTotal()'s
     * `array_key_last($summary)` bug was invisible: on the fabricated shape the
     * last element really is a row carrying 'pending', so the wrong code passed
     * every test and returned 0 against every real server.
     *
     * @return array<int, mixed>
     */
    public function xpending(string $stream, string $group, ?string $start = null, ?string $end = null, int $count = 100): array
    {
        $byOwner = [];

        foreach ($this->pending[$group][$stream] ?? [] as $entryId => $owner) {
            $byOwner[$owner][] = $entryId;
        }

        if ($start !== null || $end !== null) {
            // Extended form: one row per pending entry in the range.
            $ids = [];

            foreach ($byOwner as $owned) {
                foreach ($owned as $entryId) {
                    $ids[] = $entryId;
                }
            }

            usort($ids, fn (string $a, string $b): int => $this->compareIds($a, $b));

            $rows = [];

            foreach (array_slice($ids, 0, $count) as $entryId) {
                $idle = $this->nowMs() - ($this->deliveredAt[$group][$stream][$entryId] ?? $this->nowMs());
                $rows[] = [$entryId, $this->pending[$group][$stream][$entryId] ?? '', $idle, 1];
            }

            return $rows;
        }

        // Summary form: count first, minId and maxId second and third, the
        // per-consumer map last.
        $consumers = [];
        $total = 0;
        $minId = null;
        $maxId = null;

        foreach ($byOwner as $owner => $ids) {
            $consumers[] = [$owner, count($ids)];
            $total += count($ids);

            usort($ids, fn (string $a, string $b): int => $this->compareIds($a, $b));

            $low = $ids[0];
            $high = $ids[count($ids) - 1];

            if ($minId === null || $this->compareIds($low, $minId) < 0) {
                $minId = $low;
            }

            if ($maxId === null || $this->compareIds($high, $maxId) > 0) {
                $maxId = $high;
            }
        }

        return [$total, $minId, $maxId, $consumers];
    }

    /**
     * XAUTOCLAIM subset: claim the group's pending entries idle for at
     * least $minIdleMs, scanning from $start (inclusive, "0-0" starts at
     * the beginning) up to $count entries, transferring ownership to
     * $consumer (the claim resets the entry's idle clock, like real
     * Redis). Returns ['next' => cursor, 'entries' => [id => fields]] —
     * the phpredis reply shape, which IngestConsumer::normalizeEntries()
     * accepts as-is.
     *
     * The count is the SIXTH positional argument. It must be an int (or a
     * numeric string): LaravelRedisAdapter used to append a literal 'COUNT'
     * as the sixth and the count as the seventh, and this double silently
     * cast whichever landed at the old slot, which hid the fact that predis
     * put `COUNT COUNT` on the wire. Rejecting a non-numeric sixth argument
     * would have caught it here; instead the double now accepts exactly the
     * shape the adapter really sends.
     *
     * @return array{next: string, entries: array<string, array<string, string>>}
     */
    public function xautoclaim(string $stream, string $group, string $consumer, int $minIdleMs, string $start = '0-0', int $count = 100): array
    {
        $candidates = [];

        foreach ($this->pending[$group][$stream] ?? [] as $entryId => $owner) {
            if ($this->compareIds($entryId, $start) < 0) {
                continue;
            }

            $idle = $this->nowMs() - ($this->deliveredAt[$group][$stream][$entryId] ?? $this->nowMs());

            if ($idle < $minIdleMs) {
                continue;
            }

            $candidates[] = $entryId;
        }

        usort($candidates, fn (string $a, string $b): int => $this->compareIds($a, $b));
        $candidates = array_slice($candidates, 0, $count);

        $entries = [];
        $next = '0-0';

        foreach ($candidates as $entryId) {
            $this->pending[$group][$stream][$entryId] = $consumer;
            $this->deliveredAt[$group][$stream][$entryId] = $this->nowMs();
            $entries[$entryId] = $this->streams[$stream][$entryId] ?? [];
            $next = $entryId;
        }

        return ['next' => $next, 'entries' => $entries];
    }

    /**
     * XRANGE subset: entry id => fields in stream order between $start and
     * $end ('-' first, '+' last, optional '(' exclusive prefix on start),
     * capped at $count. Used by the dead-letter replay.
     *
     * @return array<string, array<string, string>>
     */
    public function xrange(string $stream, string $start, string $end, int $count = 100): array
    {
        $ids = array_keys($this->streams[$stream] ?? []);
        usort($ids, fn (string $a, string $b): int => $this->compareIds($a, $b));

        $selected = [];

        foreach ($ids as $entryId) {
            if (! $this->inRange($entryId, $start, $end)) {
                continue;
            }

            $selected[$entryId] = $this->streams[$stream][$entryId];

            if (count($selected) >= $count) {
                break;
            }
        }

        return $selected;
    }

    /**
     * XDEL subset: remove entries from a stream (and any group's pending
     * bookkeeping). Returns the number actually removed.
     *
     * @param  list<string>  $ids
     */
    public function xrevrange(string $stream, string $end, string $start, int $count = 100): array
    {
        $out = [];
        $ids = array_keys($this->streams[$stream] ?? []);
        rsort($ids);

        foreach ($ids as $id) {
            if ($count > 0 && count($out) >= $count) {
                break;
            }
            $out[$id] = $this->streams[$stream][$id];
        }

        return $out;
    }

    public function xdel(string $stream, array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $entryId) {
            if (isset($this->streams[$stream][$entryId])) {
                unset($this->streams[$stream][$entryId]);
                $deleted++;
            }

            foreach (array_keys($this->pending) as $group) {
                unset($this->pending[$group][$stream][$entryId], $this->deliveredAt[$group][$stream][$entryId]);
            }
        }

        return $deleted;
    }

    /**
     * Shift the test clock forward — pending entries age, so their idle
     * time crosses whatever reclaim threshold the test is exercising.
     */
    public function warpTime(int $milliseconds): void
    {
        $this->clockOffsetMs += $milliseconds;
    }

    private function nowMs(): int
    {
        return (int) (microtime(true) * 1000) + $this->clockOffsetMs;
    }

    /**
     * Stream-id order is numeric (ms, seq), not lexical — "10-0" > "9-0".
     */
    private function compareIds(string $a, string $b): int
    {
        sscanf($a, '%d-%d', $aMs, $aSeq);
        sscanf($b, '%d-%d', $bMs, $bSeq);

        return [$aMs ?? 0, $aSeq ?? 0] <=> [$bMs ?? 0, $bSeq ?? 0];
    }

    private function inRange(string $entryId, string $start, string $end): bool
    {
        $startExclusive = str_starts_with($start, '(');
        $start = $startExclusive ? substr($start, 1) : $start;

        $afterStart = $start === '-' || $this->compareIds($entryId, $start) > ($startExclusive ? 0 : -1);
        $beforeEnd = $end === '+' || $this->compareIds($entryId, $end) < 1;

        return $afterStart && $beforeEnd;
    }

    /**
     * @return list<array{0: string, 1: array<string, string>}> Entry id => field pairs, in insertion order.
     */
    public function streamEntries(string $stream): array
    {
        $entries = [];

        foreach ($this->streams[$stream] ?? [] as $id => $fields) {
            $entries[] = [$id, $fields];
        }

        return $entries;
    }

    private function nextId(): string
    {
        $ms = (int) (microtime(true) * 1000);
        if ($ms < $this->lastMs) {
            $ms = $this->lastMs;
        }
        if ($ms === $this->lastMs) {
            $this->seq++;
        } else {
            $this->lastMs = $ms;
            $this->seq = 0;
        }

        return $this->lastMs.'-'.$this->seq;
    }
}
