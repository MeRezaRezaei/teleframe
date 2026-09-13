<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.botCancelStarsSubscription (crc32 6dfa0622), returns Bool. */
final class TlPaymentsBotCancelStarsSubscriptionData extends Data
{
    public const METHOD = 'payments.botCancelStarsSubscription';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $restore,
    public mixed $userId,
    public string $chargeId,
    ) {
    }
}
