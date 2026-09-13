<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method premium.getBoostsStatus (crc32 042f1f61), returns premium.BoostsStatus. */
final class TlPremiumGetBoostsStatusData extends Data
{
    public const METHOD = 'premium.getBoostsStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
