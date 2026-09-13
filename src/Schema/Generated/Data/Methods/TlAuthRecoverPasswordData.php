<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.recoverPassword (crc32 37096c70), returns auth.Authorization. */
final class TlAuthRecoverPasswordData extends Data
{
    public const METHOD = 'auth.recoverPassword';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $code,
    public mixed $newSettings,
    ) {
    }
}
