<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Bus;

use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Bus\UpdateDedup;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use PHPUnit\Framework\TestCase;

class UpdateDedupTest extends TestCase
{
    private ArrayRedis $redis;

    private UpdateDedup $dedup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new ArrayRedis;
        $this->dedup = new UpdateDedup($this->redis);
    }

    public function test_first_time_returns_false(): void
    {
        $this->assertFalse($this->dedup->seen(1, 'hash_abc'));
    }

    public function test_after_mark_returns_true(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        $this->assertTrue($this->dedup->seen(1, 'hash_abc'));
    }

    public function test_dedup_is_per_account(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        $this->assertTrue($this->dedup->seen(1, 'hash_abc'));
        $this->assertFalse($this->dedup->seen(2, 'hash_abc'));
    }

    public function test_keys_expire(): void
    {
        $this->dedup->mark(1, 'hash_abc');
        // ArrayRedis expire is a no-op, but we verify the key exists
        $this->assertNotNull($this->redis->hget('tg:dedup:1', 'hash_abc'));
    }

    /**
     * MethodCallRemoval kill (line 42): mark() must call expire() with the TTL.
     */
    public function test_mark_sets_a_ttl_on_the_dedup_key(): void
    {
        $redis = $this->createMock(RedisConnectionContract::class);
        $redis->method('hset')->willReturn(1);
        $redis->expects(self::once())->method('expire')->with('tg:dedup:7', 3600);

        $dedup = new UpdateDedup($redis);
        $dedup->mark(7, 'hash_xyz');
    }
}
