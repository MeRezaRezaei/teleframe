<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Cache;

use InvalidArgumentException;

/**
 * Plain-PHP hot cache: entry map keyed "<bucket>/<sha>/<n>" holding
 * {bytes, atime, ctime}. No Laravel, no extensions — the in-heap tier
 * for hosts without MinIO and the reference implementation of the
 * TTL + LRU budget policy (see EvictionPolicy, which both tiers share).
 *
 * Budget is enforced lazily: put() evicts coldest until the new entry
 * fits, and rejects a single entry larger than maxBytes loudly (a chunk
 * that can never fit would otherwise thrash the whole cache out).
 */
final class MemoryHotCache implements HotCacheContract
{
    /** @var callable(): int */
    private readonly mixed $now;

    /** @var array<string, array{bytes: string, atime: int, ctime: int}> */
    private array $entries = [];

    /**
     * @param  callable(): int|null  $now  clock injector, default time()
     */
    public function __construct(
        private readonly int $ttlSeconds,
        private readonly int $maxBytes,
        ?callable $now = null,
    ) {
        $this->now = $now ?? static fn (): int => time();
    }

    public function get(string $bucket, string $objectSha, int $chunkN): string|false
    {
        $key = self::key($bucket, $objectSha, $chunkN);
        if (! isset($this->entries[$key])) {
            return false;
        }

        $entry = &$this->entries[$key];
        $entry['atime'] = ($this->now)();

        return $entry['bytes'];
    }

    public function put(string $bucket, string $objectSha, int $chunkN, string $bytes): void
    {
        $size = strlen($bytes);
        if ($size > $this->maxBytes) {
            throw new InvalidArgumentException(
                "hot cache entry {$size} bytes exceeds the {$this->maxBytes} byte budget"
            );
        }

        $key = self::key($bucket, $objectSha, $chunkN);
        $now = ($this->now)();
        if (isset($this->entries[$key])) {
            unset($this->entries[$key]);
        }
        $this->entries[$key] = ['bytes' => $bytes, 'atime' => $now, 'ctime' => $now];

        $this->enforceBudget();
    }

    public function touch(string $bucket, string $objectSha, int $chunkN): void
    {
        $key = self::key($bucket, $objectSha, $chunkN);
        if (isset($this->entries[$key])) {
            $this->entries[$key]['atime'] = ($this->now)();
        }
    }

    public function forget(string $bucket, string $objectSha, ?int $chunkN = null): void
    {
        if ($chunkN !== null) {
            unset($this->entries[self::key($bucket, $objectSha, $chunkN)]);

            return;
        }

        $prefix = $bucket.'/'.$objectSha.'/';
        foreach (array_keys($this->entries) as $key) {
            if (str_starts_with($key, $prefix)) {
                unset($this->entries[$key]);
            }
        }
    }

    public function evict(): int
    {
        $entries = [];
        foreach ($this->entries as $key => $entry) {
            $entries[] = ['key' => $key, 'bytes' => strlen($entry['bytes']), 'atime' => $entry['atime'], 'ctime' => $entry['ctime']];
        }

        $evicted = EvictionPolicy::select($entries, $this->ttlSeconds, $this->maxBytes, ($this->now)());
        foreach ($evicted as $key) {
            unset($this->entries[$key]);
        }

        return count($evicted);
    }

    public function stats(): array
    {
        $bytes = 0;
        foreach ($this->entries as $entry) {
            $bytes += strlen($entry['bytes']);
        }

        return ['entries' => count($this->entries), 'bytes' => $bytes];
    }

    private function enforceBudget(): void
    {
        foreach ($this->collectEvictable() as $key) {
            unset($this->entries[$key]);
        }
    }

    /**
     * Put-time budget enforcement is byte-budget only (TTL=0): expiry is
     * the explicit evict() sweep's job, so a put never silently ages
     * entries out from under a caller that is still filling them in.
     *
     * @return list<string>
     */
    private function collectEvictable(): array
    {
        $entries = [];
        foreach ($this->entries as $key => $entry) {
            $entries[] = ['key' => $key, 'bytes' => strlen($entry['bytes']), 'atime' => $entry['atime'], 'ctime' => $entry['ctime']];
        }

        return EvictionPolicy::select($entries, 0, $this->maxBytes, ($this->now)());
    }

    private static function key(string $bucket, string $objectSha, int $chunkN): string
    {
        return $bucket.'/'.$objectSha.'/'.$chunkN;
    }
}
