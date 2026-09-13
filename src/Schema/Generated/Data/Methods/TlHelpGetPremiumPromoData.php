<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method help.getPremiumPromo (crc32 b81b93d4), returns help.PremiumPromo. */
final class TlHelpGetPremiumPromoData extends Data
{
    public const METHOD = 'help.getPremiumPromo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
