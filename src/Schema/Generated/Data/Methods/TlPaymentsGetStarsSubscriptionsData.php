<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsSubscriptions (crc32 032512c5), returns payments.StarsStatus. */
final class TlPaymentsGetStarsSubscriptionsData extends Data
{
    public const METHOD = 'payments.getStarsSubscriptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $missingBalance,
    public mixed $peer,
    public string $offset,
    ) {
    }
}
