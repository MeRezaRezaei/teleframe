<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSavedGifs (crc32 5cf09635), returns messages.SavedGifs. */
final class TlMessagesGetSavedGifsData extends Data
{
    public const METHOD = 'messages.getSavedGifs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
