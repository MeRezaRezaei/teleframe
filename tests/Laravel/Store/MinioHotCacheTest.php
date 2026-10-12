<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Laravel\Store;

use MeRezaRezaei\Teleframe\Laravel\Store\MinioHotCache;
use PHPUnit\Framework\TestCase;

/**
 * MinioHotCache: the MinIO-backed HotCacheContract tier. Covers the
 * data-key + sidecar-meta layout, roundtrip, forget-by-object, eviction
 * sharing EvictionPolicy with MemoryHotCache, and the down-gateway path
 * (sealed Vault on this host is exactly that) reading as cold misses so
 * the store falls through to Telegram intact.
 */
final class MinioHotCacheTest extends TestCase
{
    private int $clock = 1_000;

    private const SHA_A = 'aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa';

    private const SHA_B = 'bbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb';

    private function cache(FakeS3Gateway $gateway, int $ttlSeconds = 60, int $maxBytes = 1_024): MinioHotCache
    {
        return new MinioHotCache(
            $gateway,
            $ttlSeconds,
            $maxBytes,
            now: function (): int {
                return $this->clock;
            },
        );
    }

    public function test_roundtrip_stores_data_plus_sidecar_meta(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway);

        $cache->put('tg-reports', self::SHA_A, 0, 'chunk-zero');

        $this->assertSame('chunk-zero', $cache->get('tg-reports', self::SHA_A, 0));
        $this->assertFalse($cache->get('tg-reports', self::SHA_A, 1));
        $this->assertSame(['entries' => 1, 'bytes' => 10], $cache->stats());
        $this->assertArrayHasKey('tg-reports/'.self::SHA_A.'/0.meta', $gateway->listAsMap());
    }

    public function test_get_refreshes_atime_through_sidecar(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway, ttlSeconds: 0, maxBytes: 10);

        $cache->put('b', self::SHA_A, 0, 'aaaa');
        $cache->put('b', self::SHA_A, 1, 'bbbb');

        $this->clock = 2_000;
        $cache->get('b', self::SHA_A, 0); // chunk 0 read → hot

        $this->clock = 3_000;
        $cache->put('b', self::SHA_A, 2, 'cccc'); // 12 bytes > 10 budget

        $this->assertSame(1, $cache->evict()); // chunk 1 (atime 1000) coldest
        $this->assertFalse($cache->get('b', self::SHA_A, 1));
        $this->assertSame('aaaa', $cache->get('b', self::SHA_A, 0));
        $this->assertSame('cccc', $cache->get('b', self::SHA_A, 2));
    }

    public function test_forget_single_chunk_and_whole_object(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway);

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

    public function test_evict_drops_ttl_expired_then_coldest_by_lru(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway, ttlSeconds: 100, maxBytes: 10);

        $cache->put('b', self::SHA_A, 0, 'aaaa'); // ctime 1000
        $cache->put('b', self::SHA_A, 1, 'bbbb'); // ctime 1000

        $this->clock = 2_000;
        $cache->put('b', self::SHA_A, 2, 'cc'); // ctime 2000, now 6 bytes total ≤ 10

        $this->clock = 2_050;
        $this->assertSame(2, $cache->evict()); // chunks 0+1 expired (1100 <= 2050)
        $this->assertSame(['entries' => 1, 'bytes' => 2], $cache->stats());
        $this->assertSame('cc', $cache->get('b', self::SHA_A, 2));
    }

    public function test_evict_under_budget_expires_nothing(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway, ttlSeconds: 0, maxBytes: 100);

        $cache->put('b', self::SHA_A, 0, 'keep');

        $this->clock = 1_000_000;
        $this->assertSame(0, $cache->evict());
        $this->assertSame('keep', $cache->get('b', self::SHA_A, 0));
    }

    public function test_down_gateway_reads_as_cold_miss_and_writes_are_swallowed(): void
    {
        $gateway = new FakeS3Gateway;
        $gateway->failing(true);
        $cache = $this->cache($gateway);

        // Sealed MinIO (this host): every op must fall through silently.
        $cache->put('b', self::SHA_A, 0, 'bytes');
        $this->assertFalse($cache->get('b', self::SHA_A, 0));
        $cache->touch('b', self::SHA_A, 0);
        $cache->forget('b', self::SHA_A);
        $this->assertSame(0, $cache->evict());
        $this->assertSame(['entries' => 0, 'bytes' => 0], $cache->stats());
    }

    public function test_broken_sidecar_meta_treated_as_fresh(): void
    {
        $gateway = new FakeS3Gateway;
        $cache = $this->cache($gateway, ttlSeconds: 0, maxBytes: 100);

        $cache->put('b', self::SHA_A, 0, 'aaaa');
        // Corrupt the sidecar: a lost/partial meta must not evict live data.
        $gateway->put('b/'.self::SHA_A.'/0.meta', 'not json');

        $this->clock = 1_000;
        $this->assertSame(0, $cache->evict());
        $this->assertSame('aaaa', $cache->get('b', self::SHA_A, 0));
    }
}
