<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.craftStarGift (crc32 b0f9684f), returns Updates. */
final class TlPaymentsCraftStarGiftData extends Data
{
    public const METHOD = 'payments.craftStarGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public array $stargift,
    ) {
    }
}
