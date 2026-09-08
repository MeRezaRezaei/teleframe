<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPersonalChannelHistory (crc32 55fb0996), returns messages.Messages. */
final class TlMessagesGetPersonalChannelHistoryData extends Data
{
    public const METHOD = 'messages.getPersonalChannelHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $userId,
    public int $limit,
    public int $maxId,
    public int $minId,
    public int $hash,
    ) {
    }
}
