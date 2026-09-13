<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method auth.checkPaidAuth (crc32 56e59f9c), returns auth.SentCode. */
final class TlAuthCheckPaidAuthData extends Data
{
    public const METHOD = 'auth.checkPaidAuth';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneNumber,
    public string $phoneCodeHash,
    public int $formId,
    ) {
    }
}
