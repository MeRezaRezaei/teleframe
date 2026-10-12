<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.cancelCode (crc32 1f040578), returns Bool. */
final class TlAuthCancelCodeData extends Data
{
    public const METHOD = 'auth.cancelCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public string $phoneCodeHash,
    ) {
    }
}
