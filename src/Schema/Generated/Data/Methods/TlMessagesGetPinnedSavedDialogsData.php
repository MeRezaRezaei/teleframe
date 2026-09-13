<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getPinnedSavedDialogs (crc32 d63d94e0), returns messages.SavedDialogs. */
final class TlMessagesGetPinnedSavedDialogsData extends Data
{
    public const METHOD = 'messages.getPinnedSavedDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
