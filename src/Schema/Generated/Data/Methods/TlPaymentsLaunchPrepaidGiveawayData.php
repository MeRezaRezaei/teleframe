<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.launchPrepaidGiveaway (crc32 5ff58f20), returns Updates. */
final class TlPaymentsLaunchPrepaidGiveawayData extends Data
{
    public const METHOD = 'payments.launchPrepaidGiveaway';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $giveawayId,
    public mixed $purpose,
    ) {
    }
}
