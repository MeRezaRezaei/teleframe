<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getTimezonesList (crc32 49b30240), returns help.TimezonesList. */
final class TlHelpGetTimezonesListData extends Data
{
    public const METHOD = 'help.getTimezonesList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
