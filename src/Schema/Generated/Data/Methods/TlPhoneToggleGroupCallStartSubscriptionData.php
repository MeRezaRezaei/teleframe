<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method phone.toggleGroupCallStartSubscription (crc32 219c34e6), returns Updates. */
final class TlPhoneToggleGroupCallStartSubscriptionData extends Data
{
    public const METHOD = 'phone.toggleGroupCallStartSubscription';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $call,
    public mixed $subscribed,
    ) {
    }
}
