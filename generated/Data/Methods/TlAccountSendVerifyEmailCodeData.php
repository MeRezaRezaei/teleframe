<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.sendVerifyEmailCode (crc32 98e037bb), returns account.SentEmailCode. */
final class TlAccountSendVerifyEmailCodeData extends Data
{
    public const METHOD = 'account.sendVerifyEmailCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $purpose,
    public string $email,
    ) {
    }
}
