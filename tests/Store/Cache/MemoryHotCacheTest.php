<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Store\Cache;

use InvalidArgumentException;
use MeRezaRezaei\Teleframe\Store\Cache\MemoryHotCache;
use MeRezaRezaei\Teleframe\Store\Cache\NullHotCache;
use PHPUnit\Framework\TestCase;

/**
 * MemoryHotCache: the plain-PHP TTL + LRU budget tier. Covers the
 * put/get/touch/forget surface, TTL expiry, LRU order under budget
 * pressure, oversized-entry rejection, and the NullHotCache null-object
 * behavior.
 */
final class MemoryHotCacheTest extends TestCase
{
    private int $clock = 1_000;

    private const SHA_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const SHA_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private function cache(int $ttlSeconds = 60, int $maxBytes = 1_024): MemoryHotCache
    {
        return new MemoryHotCache(
            $ttlSeconds,
            $maxBytes,
            now: function (): int {
                return $this->clock;
            },
        );
    }

    public function test_put_then_get_roundtrips_bytes(): void
    {
        $cache = $this->cache();

        $cache->put('tg-reports', self::SHA_A, 0, 'chunk-zero');
        $cache->put('tg-reports', self::SHA_A, 1, 'x');

        $this->assertSame('chunk-zero', $cache->get('tg-reports', self::SHA_A, 0));
        $this->assertSame('x', $cache->get('tg-reports', self::SHA_A, 1));
        $this->assertFalse($cache->get('tg-reports', self::SHA_A, 2));
        $this->assertFalse($cache->get('tg-other', self::SHA_A, 0));
        $this->assertSame(['entries' => 2, 'bytes' => 11], $cache->stats());
    }

    public function test_get_updates_atime_so_recently_read_entry_survives(): void
    {
        $cache = $this->cache(ttlSeconds: 0, maxBytes: 8);

        $cache->put('b', self::SHA_A, 0, 'aaaa'); // atime 1000
        $cache->put('b', self::SHA_A, 1, 'bbbb'); // atime 1000

        $this->clock = 2_000;
        $cache->get('b', self::SHA_A, 0); // chunk 0 read late → hot

        $this->clock = 3_000;
        $cache->put('b', self::SHA_A, 2, 'cccc'); // needs 4 bytes: chunk 1 (atime 1000) is coldest

        $this->assertFalse($cache->get('b', self::SHA_A, 1));
        $this->assertSame('aaaa', $cache->get('b', self::SHA_A, 0));
        $this->assertSame('cccc', $cache->get('b', self::SHA_A, 2));
    }

    public function test_touch_refreshes_atime_without_fetching_bytes(): void
    {
        $cache = $this->cache(ttlSeconds: 0, maxBytes: 5);

        $cache->put('b', self::SHA_A, 0, 'aa'); // atime 1000
        $cache->put('b', self::SHA_A, 1, 'bb'); // atime 1000

        $this->clock = 2_000;
        $cache->touch('b', self::SHA_A, 1); // chunk 1 touched → hot

        $this->clock = 3_000;
        $cache->put('b', self::SHA_A, 2, 'cc'); // needs 6 > 5 budget: chunk 0 (atime 1000) is coldest

        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $this->assertSame('bb', $cache->get('b', self::SHA_A, 1));
    }

    public function test_touch_on_missing_entry_is_a_noop(): void
    {
        $cache = $this->cache();

        $cache->touch('b', self::SHA_A, 7);

        $this->assertSame(['entries' => 0, 'bytes' => 0], $cache->stats());
    }

    public function test_forget_single_chunk_and_whole_object(): void
    {
        $cache = $this->cache();

        $cache->put('b', self::SHA_A, 0, 'zero');
        $cache->put('b', self::SHA_A, 1, 'one');
        $cache->put('b', self::SHA_B, 0, 'other');

        $cache->forget('b', self::SHA_A, 0);
        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $this->assertSame('one', $cache->get('b', self::SHA_A, 1));

        $cache->forget('b', self::SHA_A);
        $this->assertFalse($cache->get('b', self::SHA_A, 1));
        $this->assertSame('other', $cache->get('b', self::SHA_B, 0));
        $this->assertSame(['entries' => 1, 'bytes' => 5], $cache->stats());
    }

    public function test_evict_drops_ttl_expired_entries_first(): void
    {
        $cache = $this->cache(ttlSeconds: 100, maxBytes: 1_024);

        $cache->put('b', self::SHA_A, 0, 'old'); // ctime 1000
        $this->clock = 1_101;
        $cache->put('b', self::SHA_A, 1, 'new'); // ctime 1101

        $this->clock = 1_200; // chunk 0 expired (1000 + 100 <= 1200), chunk 1 alive (1201 > 1200)
        $this->assertSame(1, $cache->evict());
        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $this->assertSame('new', $cache->get('b', self::SHA_A, 1));
    }

    public function test_evict_drops_multiple_expired_and_reports_count(): void
    {
        $cache = $this->cache(ttlSeconds: 10, maxBytes: 1_024);

        $cache->put('b', self::SHA_A, 0, 'a');
        $cache->put('b', self::SHA_A, 1, 'b');
        $this->clock = 1_091;
        $cache->put('b', self::SHA_A, 2, 'c');

        $this->clock = 1_100; // chunks 0+1 expired (1010 <= 1100), chunk 2 alive (1101 > 1100)
        $this->assertSame(2, $cache->evict());
        $this->assertSame(['entries' => 1, 'bytes' => 1], $cache->stats());
        $this->assertSame('c', $cache->get('b', self::SHA_A, 2));
    }

    public function test_put_enforces_budget_lazily_by_lru(): void
    {
        $cache = $this->cache(ttlSeconds: 0, maxBytes: 10);

        $cache->put('b', self::SHA_A, 0, '12345');
        $this->clock = 2_000;
        $cache->put('b', self::SHA_A, 1, '67890');
        $this->clock = 3_000;
        $cache->put('b', self::SHA_A, 2, 'abc'); // 13 bytes > 10: chunk 0 evicted lazily

        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $this->assertSame(['entries' => 2, 'bytes' => 8], $cache->stats());
        $this->assertSame(0, $cache->evict()); // already under budget
    }

    public function test_ttl_zero_disables_expiry(): void
    {
        $cache = $this->cache(ttlSeconds: 0, maxBytes: 1_024);

        $cache->put('b', self::SHA_A, 0, 'forever');

        $this->clock = 1_000_000_000;
        $this->assertSame(0, $cache->evict());
        $this->assertSame('forever', $cache->get('b', self::SHA_A, 0));
    }

    public function test_put_rejects_entry_larger_than_budget(): void
    {
        $cache = $this->cache(maxBytes: 4);

        $this->expectException(InvalidArgumentException::class);
        $cache->put('b', self::SHA_A, 0, 'too-big');
    }

    public function test_oversized_rejection_leaves_existing_entries_intact(): void
    {
        $cache = $this->cache(maxBytes: 8);

        $cache->put('b', self::SHA_A, 0, 'keep');

        try {
            $cache->put('b', self::SHA_A, 1, 'way-too-big');
            $this->fail('expected InvalidArgumentException');
        } catch (InvalidArgumentException) {
            // rejected loudly, cache contents untouched
        }

        $this->assertSame(['entries' => 1, 'bytes' => 4], $cache->stats());
        $this->assertSame('keep', $cache->get('b', self::SHA_A, 0));
    }

    public function test_re_put_over_budget_evicts_itself_first(): void
    {
        $cache = $this->cache(maxBytes: 4);

        $cache->put('b', self::SHA_A, 0, 'old!');
        $cache->put('b', self::SHA_A, 0, 'new!');

        $this->assertSame('new!', $cache->get('b', self::SHA_A, 0));
        $this->assertSame(['entries' => 1, 'bytes' => 4], $cache->stats());
    }

    public function test_null_cache_always_misses_and_neops(): void
    {
        $cache = new NullHotCache;

        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $cache->put('b', self::SHA_A, 0, 'bytes');
        $cache->touch('b', self::SHA_A, 0);
        $cache->forget('b', self::SHA_A);
        $this->assertSame(0, $cache->evict());
        $this->assertSame(['entries' => 0, 'bytes' => 0], $cache->stats());
    }
}
