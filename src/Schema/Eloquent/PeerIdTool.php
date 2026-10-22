<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Eloquent;

use InvalidArgumentException;

/**
 * Telegram's canonical peer-to-long encoding (the documented scheme used by
 * official libs): peerUser(id) -> +id; peerChat(id) -> -id;
 * peerChannel(id) -> -(2^32) - id. Decoding decides by value ranges, so one
 * bigint column per Peer ref mirrors Telegram's own "long peer" identity:
 * indexable, tenant-neutral, and provably isolated per account.
 */
final class PeerIdTool
{
    /** Channel ids live below -(2^32); chat ids in (-(2^32), 0); users above 0. */
    private const CHANNEL_BASE = -(1 << 32);

    public static function userLong(int $userId): int
    {
        return $userId;
    }

    public static function chatLong(int $chatId): int
    {
        return -$chatId;
    }

    public static function channelLong(int $channelId): int
    {
        return self::CHANNEL_BASE - $channelId;
    }

    /** @return array{kind:'user'|'chat'|'channel', id:int} */
    public static function decode(int $long): array
    {
        if ($long > 0) {
            return ['kind' => 'user', 'id' => $long];
        }
        if ($long < self::CHANNEL_BASE) {
            return ['kind' => 'channel', 'id' => -(int) ($long - self::CHANNEL_BASE)];
        }
        if ($long < 0) {
            return ['kind' => 'chat', 'id' => -$long];
        }
        throw new InvalidArgumentException("Invalid peer long: {$long}");
    }

    public static function isUser(int $long): bool
    {
        return $long > 0;
    }
}