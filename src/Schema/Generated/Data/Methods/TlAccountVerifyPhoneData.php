<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.verifyPhone (crc32 4dd3a7f6), returns Bool. */
final class TlAccountVerifyPhoneData extends Data
{
    public const METHOD = 'account.verifyPhone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public string $phoneCodeHash,
    public string $phoneCode,
    ) {
    }
}
