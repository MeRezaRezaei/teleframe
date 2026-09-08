<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.changeStarsSubscription (crc32 c7770878), returns Bool. */
final class TlPaymentsChangeStarsSubscriptionData extends Data
{
    public const METHOD = 'payments.changeStarsSubscription';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public string $subscriptionId,
    public mixed $canceled,
    ) {
    }
}
