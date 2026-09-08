<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDefaultTagReactions (crc32 bdf93428), returns messages.Reactions. */
final class TlMessagesGetDefaultTagReactionsData extends Data
{
    public const METHOD = 'messages.getDefaultTagReactions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
