<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getNearestDc (crc32 1fb33026), returns NearestDc. */
final class TlHelpGetNearestDcData extends Data
{
    public const METHOD = 'help.getNearestDc';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
