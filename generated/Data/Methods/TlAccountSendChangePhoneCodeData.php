<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.sendChangePhoneCode (crc32 82574ae5), returns auth.SentCode. */
final class TlAccountSendChangePhoneCodeData extends Data
{
    public const METHOD = 'account.sendChangePhoneCode';

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
