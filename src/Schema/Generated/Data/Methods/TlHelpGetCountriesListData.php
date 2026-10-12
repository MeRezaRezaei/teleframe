<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getCountriesList (crc32 735787a8), returns help.CountriesList. */
final class TlHelpGetCountriesListData extends Data
{
    public const METHOD = 'help.getCountriesList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $langCode,
    public int $hash,
    ) {
    }
}
