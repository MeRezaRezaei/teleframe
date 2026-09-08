<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.sendConfirmPhoneCode (crc32 1b3faa88), returns auth.SentCode. */
final class TlAccountSendConfirmPhoneCodeData extends Data
{
    public const METHOD = 'account.sendConfirmPhoneCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $hash,
    public mixed $settings,
    ) {
    }
}
