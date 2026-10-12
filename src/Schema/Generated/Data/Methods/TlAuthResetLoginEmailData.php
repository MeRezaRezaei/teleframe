<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.resetLoginEmail (crc32 7e960193), returns auth.SentCode. */
final class TlAuthResetLoginEmailData extends Data
{
    public const METHOD = 'auth.resetLoginEmail';

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
