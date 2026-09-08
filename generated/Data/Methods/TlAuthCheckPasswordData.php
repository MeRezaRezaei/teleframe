<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.checkPassword (crc32 d18b4d16), returns auth.Authorization. */
final class TlAuthCheckPasswordData extends Data
{
    public const METHOD = 'auth.checkPassword';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $password,
    ) {
    }
}
