<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.requestPasswordRecovery (crc32 d897bc66), returns auth.PasswordRecovery. */
final class TlAuthRequestPasswordRecoveryData extends Data
{
    public const METHOD = 'auth.requestPasswordRecovery';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
