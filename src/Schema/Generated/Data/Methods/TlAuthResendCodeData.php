<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.resendCode (crc32 cae47523), returns auth.SentCode. */
final class TlAuthResendCodeData extends Data
{
    public const METHOD = 'auth.resendCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public string $phoneNumber,
    public string $phoneCodeHash,
    public ?string $reason,
    ) {
    }
}
