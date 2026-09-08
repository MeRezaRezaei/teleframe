<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAllStickers (crc32 b8a0a1a8), returns messages.AllStickers. */
final class TlMessagesGetAllStickersData extends Data
{
    public const METHOD = 'messages.getAllStickers';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
