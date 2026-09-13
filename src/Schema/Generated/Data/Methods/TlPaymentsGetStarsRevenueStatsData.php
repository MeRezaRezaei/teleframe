<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsRevenueStats (crc32 d91ffad6), returns payments.StarsRevenueStats. */
final class TlPaymentsGetStarsRevenueStatsData extends Data
{
    public const METHOD = 'payments.getStarsRevenueStats';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $dark,
    public ?bool $ton,
    public mixed $peer,
    ) {
    }
}
