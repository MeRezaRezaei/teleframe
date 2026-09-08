<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsGiveawayOptions (crc32 bd1efd3e), returns Vector<StarsGiveawayOption>. */
final class TlPaymentsGetStarsGiveawayOptionsData extends Data
{
    public const METHOD = 'payments.getStarsGiveawayOptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
