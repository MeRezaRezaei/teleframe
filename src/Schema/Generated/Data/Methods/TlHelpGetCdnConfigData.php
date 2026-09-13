<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getCdnConfig (crc32 52029342), returns CdnConfig. */
final class TlHelpGetCdnConfigData extends Data
{
    public const METHOD = 'help.getCdnConfig';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
