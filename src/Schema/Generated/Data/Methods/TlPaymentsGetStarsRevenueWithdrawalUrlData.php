<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsRevenueWithdrawalUrl (crc32 2433dc92), returns payments.StarsRevenueWithdrawalUrl. */
final class TlPaymentsGetStarsRevenueWithdrawalUrlData extends Data
{
    public const METHOD = 'payments.getStarsRevenueWithdrawalUrl';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $ton,
    public mixed $peer,
    public ?int $amount,
    public mixed $password,
    ) {
    }
}
