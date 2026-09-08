<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.verifyEmail (crc32 032da4cf), returns account.EmailVerified. */
final class TlAccountVerifyEmailData extends Data
{
    public const METHOD = 'account.verifyEmail';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $purpose,
    public mixed $verification,
    ) {
    }
}
