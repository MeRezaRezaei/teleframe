<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method langpack.getDifference (crc32 cd984aa5), returns LangPackDifference. */
final class TlLangpackGetDifferenceData extends Data
{
    public const METHOD = 'langpack.getDifference';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langPack,
    public string $langCode,
    public int $fromVersion,
    ) {
    }
}
