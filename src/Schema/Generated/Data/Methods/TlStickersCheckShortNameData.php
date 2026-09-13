<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method stickers.checkShortName (crc32 284b3639), returns Bool. */
final class TlStickersCheckShortNameData extends Data
{
    public const METHOD = 'stickers.checkShortName';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $shortName,
    ) {
    }
}
