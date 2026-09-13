<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getAppConfig (crc32 61e3f854), returns help.AppConfig. */
final class TlHelpGetAppConfigData extends Data
{
    public const METHOD = 'help.getAppConfig';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
