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

    /** @var array<string, list<callable(string, string): void>> */
    private array $subscribers = [];

    private int $lastMs = 0;

    private int $seq = -1;

    public function xadd(string $stream, string $id, array $fields): string
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

        return $id;
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

    public function hgetall(string $key): array
    {
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

        return $this->lastMs . '-' . $this->seq;
    }
}
