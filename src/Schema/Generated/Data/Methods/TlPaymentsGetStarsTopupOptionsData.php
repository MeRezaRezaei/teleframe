<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsTopupOptions (crc32 c00ec7d3), returns Vector<StarsTopupOption>. */
final class TlPaymentsGetStarsTopupOptionsData extends Data
{
    public const METHOD = 'payments.getStarsTopupOptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
