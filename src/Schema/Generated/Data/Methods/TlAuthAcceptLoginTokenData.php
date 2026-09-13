<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.acceptLoginToken (crc32 e894ad4d), returns Authorization. */
final class TlAuthAcceptLoginTokenData extends Data
{
    public const METHOD = 'auth.acceptLoginToken';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $token,
    ) {
    }
}
