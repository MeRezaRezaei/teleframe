<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.sendStarGiftOffer (crc32 8fb86b41), returns Updates. */
final class TlPaymentsSendStarGiftOfferData extends Data
{
    public const METHOD = 'payments.sendStarGiftOffer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public string $slug,
    public mixed $price,
    public int $duration,
    public int $randomId,
    public ?int $allowPaidStars,
    ) {
    }
}
