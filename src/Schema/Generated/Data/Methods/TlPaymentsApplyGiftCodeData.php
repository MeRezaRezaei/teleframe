<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.applyGiftCode (crc32 f6e26854), returns Updates. */
final class TlPaymentsApplyGiftCodeData extends Data
{
    public const METHOD = 'payments.applyGiftCode';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $slug,
    ) {
    }
}
