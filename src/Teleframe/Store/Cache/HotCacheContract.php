<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Cache;

/**
 * TStore hot cache (verbatim S6): per-channel "bucket" chunk cache in
 * MinIO. Read path: cache -> miss -> Telegram -> populate -> serve;
 * every read touches; eviction = TTL first, then coldest by atime until
 * under the max_bytes budget. The contract is failure-tolerant by
 * design — a down/unconfigured cache reads as a miss so the public API
 * falls through to Telegram intact.
 */
interface HotCacheContract
{
    /** @return string|false chunk bytes, false on miss */
    public function get(string $bucket, string $objectSha, int $chunkN): string|false;

    public function put(string $bucket, string $objectSha, int $chunkN, string $bytes): void;

    public function touch(string $bucket, string $objectSha, int $chunkN): void;

    public function forget(string $bucket, string $objectSha, ?int $chunkN = null): void;

    /**
     * Evict cold entries: TTL-expired first, then coldest by atime until
     * under max_bytes. Returns evicted count.
     */
    public function evict(): int;

    /**
     * Total bytes currently cached (best-effort).
     *
     * @return array{entries: int, bytes: int}
     */
    public function stats(): array;
}
