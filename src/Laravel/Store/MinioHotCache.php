<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Store;

use JsonException;
use MeRezaRezaei\Teleframe\Store\Cache\EvictionPolicy;
use MeRezaRezaei\Teleframe\Store\Cache\HotCacheContract;

/**
 * MinIO hot-cache tier (verbatim S6): HotCacheContract over an
 * S3GatewayContract. Data lives at "<bucket>/<objectSha>/<chunkN>";
 * each entry carries a sibling "<key>.meta" object with JSON
 * {atime, ctime} (the gateway surface is metadata-free by design, so
 * the sidecar keeps the policy pure).
 *
 * Eviction shares EvictionPolicy with MemoryHotCache so both tiers
 * apply identical TTL + LRU math. A down gateway reads as a miss on
 * every operation — the store falls through to Telegram and the API
 * stays intact; writes/sweeps fail soft the same way.
 */
final class MinioHotCache implements HotCacheContract
{
    /** @var callable(): int */
    private readonly mixed $now;

    /**
     * @param  callable(): int|null  $now  clock injector, default time()
     */
    public function __construct(
        private readonly S3GatewayContract $gateway,
        private readonly int $ttlSeconds,
        private readonly int $maxBytes,
        ?callable $now = null,
    ) {
        $this->now = $now ?? static fn (): int => time();
    }

    public function get(string $bucket, string $objectSha, int $chunkN): string|false
    {
        $key = self::dataKey($bucket, $objectSha, $chunkN);

        try {
            $bytes = $this->gateway->get($key);
        } catch (\Throwable) {
            return false;
        }
        if ($bytes === false) {
            return false;
        }

        $this->writeMeta($key, $this->readMeta($key));

        return $bytes;
    }

    public function put(string $bucket, string $objectSha, int $chunkN, string $bytes): void
    {
        $key = self::dataKey($bucket, $objectSha, $chunkN);
        $now = ($this->now)();

        try {
            $this->gateway->put($key, $bytes);
            $this->gateway->put(self::metaKey($key), self::encodeMeta($now, $now));
        } catch (\Throwable) {
            // Cache down = fall through; the store re-fetches from Telegram.
        }
    }

    public function touch(string $bucket, string $objectSha, int $chunkN): void
    {
        $key = self::dataKey($bucket, $objectSha, $chunkN);
        $this->writeMeta($key, $this->readMeta($key));
    }

    public function forget(string $bucket, string $objectSha, ?int $chunkN = null): void
    {
        if ($chunkN !== null) {
            $this->drop(self::dataKey($bucket, $objectSha, $chunkN));

            return;
        }

        try {
            $prefix = $bucket.'/'.$objectSha.'/';
            foreach ($this->gateway->list() as $object) {
                if (self::isDataKey($object['key']) && str_starts_with($object['key'], $prefix)) {
                    $this->drop($object['key']);
                }
            }
        } catch (\Throwable) {
            // Down gateway: nothing to forget.
        }
    }

    public function evict(): int
    {
        try {
            $entries = [];
            foreach ($this->gateway->list() as $object) {
                if (! self::isDataKey($object['key'])) {
                    continue;
                }
                $meta = $this->readMeta($object['key']);
                $entries[] = [
                    'key' => $object['key'],
                    'bytes' => $object['size'],
                    'atime' => $meta['atime'],
                    'ctime' => $meta['ctime'],
                ];
            }
        } catch (\Throwable) {
            return 0;
        }

        $evicted = EvictionPolicy::select($entries, $this->ttlSeconds, $this->maxBytes, ($this->now)());
        foreach ($evicted as $key) {
            $this->drop($key);
        }

        return count($evicted);
    }

    public function stats(): array
    {
        try {
            $entries = 0;
            $bytes = 0;
            foreach ($this->gateway->list() as $object) {
                if (! self::isDataKey($object['key'])) {
                    continue;
                }
                $entries++;
                $bytes += $object['size'];
            }
        } catch (\Throwable) {
            return ['entries' => 0, 'bytes' => 0];
        }

        return ['entries' => $entries, 'bytes' => $bytes];
    }

    /**
     * @param  array{atime: int, ctime: int}  $meta
     */
    private function writeMeta(string $key, array $meta): void
    {
        try {
            $this->gateway->put(self::metaKey($key), self::encodeMeta(($this->now)(), $meta['ctime']));
        } catch (\Throwable) {
            // Best-effort touch; a failed atime update only ages the entry.
        }
    }

    /**
     * @return array{atime: int, ctime: int}
     */
    private function readMeta(string $key): array
    {
        try {
            $raw = $this->gateway->get(self::metaKey($key));
        } catch (\Throwable) {
            $raw = false;
        }
        if ($raw === false) {
            // Missing/unreadable meta: treat the entry as fresh-created
            // now so a lost sidecar never evicts live data spuriously.
            $now = ($this->now)();

            return ['atime' => $now, 'ctime' => $now];
        }

        try {
            $decoded = json_decode($raw, true, 2, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $now = ($this->now)();

            return ['atime' => $now, 'ctime' => $now];
        }

        $atime = is_int($decoded['atime'] ?? null) ? $decoded['atime'] : ($this->now)();
        $ctime = is_int($decoded['ctime'] ?? null) ? $decoded['ctime'] : ($this->now)();

        return ['atime' => $atime, 'ctime' => $ctime];
    }

    private function drop(string $key): void
    {
        try {
            $this->gateway->delete($key);
            $this->gateway->delete(self::metaKey($key));
        } catch (\Throwable) {
            // Fall through; the next sweep retries.
        }
    }

    private static function dataKey(string $bucket, string $objectSha, int $chunkN): string
    {
        return $bucket.'/'.$objectSha.'/'.$chunkN;
    }

    private static function metaKey(string $key): string
    {
        return $key.'.meta';
    }

    private static function isDataKey(string $key): bool
    {
        return ! str_ends_with($key, '.meta');
    }

    private static function encodeMeta(int $atime, int $ctime): string
    {
        try {
            return json_encode(['atime' => $atime, 'ctime' => $ctime], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return '{"atime":'.$atime.',"ctime":'.$ctime.'}';
        }
    }
}
