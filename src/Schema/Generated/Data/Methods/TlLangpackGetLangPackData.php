<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method langpack.getLangPack (crc32 f2f2330a), returns LangPackDifference. */
final class TlLangpackGetLangPackData extends Data
{
    public const METHOD = 'langpack.getLangPack';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langPack,
    public string $langCode,
    ) {
    }
}
