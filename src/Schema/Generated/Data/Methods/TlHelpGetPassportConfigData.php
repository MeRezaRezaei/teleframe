<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getPassportConfig (crc32 c661ad08), returns help.PassportConfig. */
final class TlHelpGetPassportConfigData extends Data
{
    public const METHOD = 'help.getPassportConfig';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
