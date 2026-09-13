<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.resendPasswordEmail (crc32 7a7f2a15), returns Bool. */
final class TlAccountResendPasswordEmailData extends Data
{
    public const METHOD = 'account.resendPasswordEmail';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
