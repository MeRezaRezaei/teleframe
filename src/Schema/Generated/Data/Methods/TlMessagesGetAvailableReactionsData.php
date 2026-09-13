<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAvailableReactions (crc32 18dea0ac), returns messages.AvailableReactions. */
final class TlMessagesGetAvailableReactionsData extends Data
{
    public const METHOD = 'messages.getAvailableReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
