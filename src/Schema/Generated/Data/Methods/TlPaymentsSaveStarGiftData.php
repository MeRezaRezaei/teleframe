<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.saveStarGift (crc32 2a2a697c), returns Bool. */
final class TlPaymentsSaveStarGiftData extends Data
{
    public const METHOD = 'payments.saveStarGift';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $unsave,
    public mixed $stargift,
    ) {
    }
}
