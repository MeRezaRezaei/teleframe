<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Cache;

/**
 * The "none" hot cache: every read misses, every write vanishes. The
 * default binding when tstore.cache is unconfigured so the store serves
 * straight from Telegram with zero moving parts.
 */
final class NullHotCache implements HotCacheContract
{
    public function get(string $bucket, string $objectSha, int $chunkN): string|false
    {
        return false;
    }

    public function put(string $bucket, string $objectSha, int $chunkN, string $bytes): void {}

    public function touch(string $bucket, string $objectSha, int $chunkN): void {}

    public function forget(string $bucket, string $objectSha, ?int $chunkN = null): void {}

    public function evict(): int
    {
        return 0;
    }

    public function stats(): array
    {
        return ['entries' => 0, 'bytes' => 0];
    }
}
