<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method account.confirmPhone (crc32 5f2178c3), returns Bool. */
final class TlAccountConfirmPhoneData extends Data
{
    public const METHOD = 'account.confirmPhone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $phoneCodeHash,
    public string $phoneCode,
    ) {
    }
}
