<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.initPasskeyLogin (crc32 518ad0b7), returns auth.PasskeyLoginOptions. */
final class TlAuthInitPasskeyLoginData extends Data
{
    public const METHOD = 'auth.initPasskeyLogin';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $apiId,
    public string $apiHash,
    ) {
    }
}
