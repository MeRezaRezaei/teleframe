<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getMaskStickers (crc32 640f82b8), returns messages.AllStickers. */
final class TlMessagesGetMaskStickersData extends Data
{
    public const METHOD = 'messages.getMaskStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
