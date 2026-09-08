<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.importLoginToken (crc32 95ac5ce4), returns auth.LoginToken. */
final class TlAuthImportLoginTokenData extends Data
{
    public const METHOD = 'auth.importLoginToken';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $token,
    ) {
    }
}
