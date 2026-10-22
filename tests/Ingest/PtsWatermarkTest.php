<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use MeRezaRezaei\Teleframe\Tests\Schema\TestCase;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;

class PtsWatermarkTest extends TestCase
{
    private ArrayRedis $redis;
    private PtsWatermark $watermark;

    protected function setUp(): void
    {
        parent::setUp();
        $this->redis = new ArrayRedis();
        $this->watermark = new PtsWatermark($this->redis);

        // Clean slate
        $this->redis->del('tg:pts:100', 'tg:pts:100:channels');
    }

    public function test_get_returns_null_for_unknown_account(): void
    {
        $this->assertNull($this->watermark->get(999));
    }

    public function test_put_and_get_roundtrip(): void
    {
        $state = ['pts' => 42, 'date' => 1700000000, 'qts' => 0, 'seq' => 5];
        $this->watermark->put(100, $state);

        $loaded = $this->watermark->get(100);
        $this->assertSame($state, $loaded);
    }

    public function test_channel_pts_roundtrip(): void
    {
        $this->watermark->touchChannel(100, 200, 10);
        $this->watermark->touchChannel(100, 200, 15);
        $this->watermark->touchChannel(100, 200, 12); // monotonic: stays 15

        $this->assertSame(15, $this->watermark->getChannelPts(100, 200));
        $this->assertNull($this->watermark->getChannelPts(100, 999));
    }

    public function test_channel_pts_is_per_account_isolated(): void
    {
        $this->watermark->touchChannel(100, 200, 10);
        $this->watermark->touchChannel(200, 200, 50);

        $this->assertSame(10, $this->watermark->getChannelPts(100, 200));
        $this->assertSame(50, $this->watermark->getChannelPts(200, 200));
    }
}
