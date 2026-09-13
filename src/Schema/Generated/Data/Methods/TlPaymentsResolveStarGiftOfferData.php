<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.resolveStarGiftOffer (crc32 e9ce781c), returns Updates. */
final class TlPaymentsResolveStarGiftOfferData extends Data
{
    public const METHOD = 'payments.resolveStarGiftOffer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $decline,
    public int $offerMsgId,
    ) {
    }
}
