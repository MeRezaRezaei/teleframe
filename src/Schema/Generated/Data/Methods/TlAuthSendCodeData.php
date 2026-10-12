<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.sendCode (crc32 a677244f), returns auth.SentCode. */
final class TlAuthSendCodeData extends Data
{
    public const METHOD = 'auth.sendCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public int $apiId,
    public string $apiHash,
    public mixed $settings,
    ) {
    }
}
