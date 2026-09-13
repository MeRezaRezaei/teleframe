<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resetPassword (crc32 9308ce1b), returns account.ResetPasswordResult. */
final class TlAccountResetPasswordData extends Data
{
    public const METHOD = 'account.resetPassword';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
