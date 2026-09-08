<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.sendVerifyPhoneCode (crc32 a5a356f9), returns auth.SentCode. */
final class TlAccountSendVerifyPhoneCodeData extends Data
{
    public const METHOD = 'account.sendVerifyPhoneCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public mixed $settings,
    ) {
    }
}
