<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getFavedStickers (crc32 04f1aaa9), returns messages.FavedStickers. */
final class TlMessagesGetFavedStickersData extends Data
{
    public const METHOD = 'messages.getFavedStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
