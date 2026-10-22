<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Realtime;

use MeRezaRezaei\Teleframe\Realtime\ChannelNames;
use PHPUnit\Framework\TestCase;

final class ChannelNamesTest extends TestCase
{
    public function testAccountUpdatesFormat(): void
    {
        self::assertSame('account:42:updates', ChannelNames::accountUpdates(42));
    }

    public function testAccountMessagesFormat(): void
    {
        self::assertSame('account:42:messages:99', ChannelNames::accountMessages(42, 99));
    }

    public function testAccountUpdatesWithZeroId(): void
    {
        self::assertSame('account:0:updates', ChannelNames::accountUpdates(0));
    }

    public function testAccountMessagesWithLargeIds(): void
    {
        self::assertSame(
            'account:999999999:messages:888888888',
            ChannelNames::accountMessages(999999999, 888888888),
        );
    }
}
