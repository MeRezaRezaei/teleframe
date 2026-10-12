<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Cache;

use MeRezaRezaei\Teleframe\Store\Cache\EvictionPolicy;
use PHPUnit\Framework\TestCase;

/**
 * EvictionPolicy: the pure TTL + LRU math shared by both hot-cache
 * tiers. Zero I/O — every case feeds a hand-built entries list and
 * asserts the eviction order.
 */
final class EvictionPolicyTest extends TestCase
{
    public function test_returns_nothing_when_everything_fits(): void
    {
        $this->assertSame([], EvictionPolicy::select(
            [['key' => 'a', 'bytes' => 10, 'atime' => 1, 'ctime' => 1]],
            ttlSeconds: 60,
            maxBytes: 100,
            now: 10,
        ));
    }

    public function test_ttl_expired_entries_go_first_regardless_of_atime(): void
    {
        $keys = EvictionPolicy::select([
            ['key' => 'hot-but-old', 'bytes' => 10, 'atime' => 90, 'ctime' => 1],
            ['key' => 'fresh', 'bytes' => 10, 'atime' => 2, 'ctime' => 85],
        ], ttlSeconds: 60, maxBytes: 100, now: 100);

        $this->assertSame(['hot-but-old'], $keys);
    }

    public function test_ttl_boundary_at_exact_expiry_is_expired(): void
    {
        $keys = EvictionPolicy::select(
            [['key' => 'edge', 'bytes' => 1, 'atime' => 5, 'ctime' => 40]],
            ttlSeconds: 60,
            maxBytes: 100,
            now: 100,
        );

        $this->assertSame(['edge'], $keys);
    }

    public function test_ttl_zero_disables_expiry(): void
    {
        $keys = EvictionPolicy::select(
            [['key' => 'ancient', 'bytes' => 1, 'atime' => 1, 'ctime' => 1]],
            ttlSeconds: 0,
            maxBytes: 100,
            now: 1_000_000,
        );

        $this->assertSame([], $keys);
    }

    public function test_lru_evicts_coldest_by_atime_until_under_budget(): void
    {
        $keys = EvictionPolicy::select([
            ['key' => 'newest', 'bytes' => 5, 'atime' => 30, 'ctime' => 1],
            ['key' => 'middle', 'bytes' => 5, 'atime' => 20, 'ctime' => 1],
            ['key' => 'oldest', 'bytes' => 5, 'atime' => 10, 'ctime' => 1],
        ], ttlSeconds: 0, maxBytes: 6, now: 100);

        $this->assertSame(['oldest', 'middle'], $keys);
    }

    public function test_lru_stops_as_soon_as_budget_is_met(): void
    {
        $keys = EvictionPolicy::select([
            ['key' => 'newest', 'bytes' => 5, 'atime' => 30, 'ctime' => 1],
            ['key' => 'middle', 'bytes' => 5, 'atime' => 20, 'ctime' => 1],
            ['key' => 'oldest', 'bytes' => 5, 'atime' => 10, 'ctime' => 1],
        ], ttlSeconds: 0, maxBytes: 10, now: 100);

        $this->assertSame(['oldest'], $keys);
    }

    public function test_expired_keys_counted_before_budget_check(): void
    {
        // Expiring the TTL-expired key already fits the budget; the LRU
        // pass must not fire and evict anything else.
        $keys = EvictionPolicy::select([
            ['key' => 'expired', 'bytes' => 50, 'atime' => 1, 'ctime' => 1],
            ['key' => 'live', 'bytes' => 40, 'atime' => 5, 'ctime' => 50],
        ], ttlSeconds: 30, maxBytes: 100, now: 79);

        $this->assertSame(['expired'], $keys);
    }

    public function test_empty_entries_select_nothing(): void
    {
        $this->assertSame([], EvictionPolicy::select(
            [],
            ttlSeconds: 60,
            maxBytes: 0,
            now: 0,
        ));
    }

    public function test_budget_zero_evicts_everything(): void
    {
        $keys = EvictionPolicy::select([
            ['key' => 'a', 'bytes' => 3, 'atime' => 2, 'ctime' => 2],
            ['key' => 'b', 'bytes' => 1, 'atime' => 1, 'ctime' => 1],
        ], ttlSeconds: 0, maxBytes: 0, now: 5);

        $this->assertSame(['b', 'a'], $keys);
    }
}
