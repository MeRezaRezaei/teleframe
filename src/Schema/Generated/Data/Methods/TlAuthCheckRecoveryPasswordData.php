<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.checkRecoveryPassword (crc32 0d36bf79), returns Bool. */
final class TlAuthCheckRecoveryPasswordData extends Data
{
    public const METHOD = 'auth.checkRecoveryPassword';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $code,
    ) {
    }
}
