<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.reportMissingCode (crc32 cb9deff6), returns Bool. */
final class TlAuthReportMissingCodeData extends Data
{
    public const METHOD = 'auth.reportMissingCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public string $phoneCodeHash,
    public string $mnc,
    ) {
    }
}
