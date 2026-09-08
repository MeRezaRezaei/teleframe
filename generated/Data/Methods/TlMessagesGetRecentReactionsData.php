<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getRecentReactions (crc32 39461db2), returns messages.Reactions. */
final class TlMessagesGetRecentReactionsData extends Data
{
    public const METHOD = 'messages.getRecentReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $limit,
    public int $hash,
    ) {
    }
}
