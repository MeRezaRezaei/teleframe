<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use PHPUnit\Framework\TestCase;

final class PeerIdToolTest extends TestCase
{
    public function test_user_long_is_positive_id(): void
    {
        self::assertSame(777, PeerIdTool::userLong(777));
        self::assertTrue(PeerIdTool::isUser(777));
        self::assertSame(['kind' => 'user', 'id' => 777], PeerIdTool::decode(777));
    }

    public function test_chat_long_is_negated_id(): void
    {
        $long = PeerIdTool::chatLong(42);
        self::assertSame(-42, $long);
        self::assertSame(['kind' => 'chat', 'id' => 42], PeerIdTool::decode($long));
    }

    public function test_channel_long_is_below_negative_2to31(): void
    {
        $long = PeerIdTool::channelLong(100);
        self::assertLessThan(-(1 << 31), $long);
        self::assertSame(['kind' => 'channel', 'id' => 100], PeerIdTool::decode($long));
        self::assertFalse(PeerIdTool::isUser($long));
    }

    public function test_roundtrip_all_three(): void
    {
        foreach ([
            PeerIdTool::userLong(7),
            PeerIdTool::chatLong(7),
            PeerIdTool::channelLong(7),
        ] as $long) {
            $decoded = PeerIdTool::decode($long);
            $re = match ($decoded['kind']) {
                'user' => PeerIdTool::userLong($decoded['id']),
                'chat' => PeerIdTool::chatLong($decoded['id']),
                'channel' => PeerIdTool::channelLong($decoded['id']),
            };
            self::assertSame($long, $re);
        }
    }

    public function test_zero_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PeerIdTool::decode(0);
    }
}