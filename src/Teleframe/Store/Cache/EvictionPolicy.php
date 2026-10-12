<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Cache;

/**
 * Shared TTL + LRU eviction policy (verbatim S6): pure math, zero I/O.
 * Given an entries list and a budget, decide which keys die:
 *
 *  1. every entry whose TTL expired (ctime + ttl <= now) goes first,
 *  2. then the coldest by atime until the total fits max_bytes.
 *
 * MemoryHotCache and MinioHotCache both route their evict() decisions
 * through this class so the in-heap and MinIO tiers evict identically.
 */
final class EvictionPolicy
{
    /**
     * @param  list<array{key: string, bytes: int, atime: int, ctime: int}>  $entries
     * @return list<string> keys to evict, in eviction order
     */
    public static function select(
        array $entries,
        int $ttlSeconds,
        int $maxBytes,
        int $now,
    ): array {
        $live = [];
        $evicted = [];
        $bytes = 0;

        foreach ($entries as $entry) {
            if ($ttlSeconds > 0 && $now >= $entry['ctime'] + $ttlSeconds) {
                $evicted[] = $entry['key'];

                continue;
            }
            $live[] = $entry;
            $bytes += $entry['bytes'];
        }

        if ($bytes > $maxBytes) {
            usort($live, static fn (array $a, array $b): int => $a['atime'] <=> $b['atime']);

            foreach ($live as $entry) {
                if ($bytes <= $maxBytes) {
                    break;
                }
                $evicted[] = $entry['key'];
                $bytes -= $entry['bytes'];
            }
        }

        return $evicted;
    }
}
