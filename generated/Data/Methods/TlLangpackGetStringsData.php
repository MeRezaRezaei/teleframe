<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method langpack.getStrings (crc32 efea3803), returns Vector<LangPackString>. */
final class TlLangpackGetStringsData extends Data
{
    public const METHOD = 'langpack.getStrings';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langPack,
    public string $langCode,
    public array $keys,
    ) {
    }
}
