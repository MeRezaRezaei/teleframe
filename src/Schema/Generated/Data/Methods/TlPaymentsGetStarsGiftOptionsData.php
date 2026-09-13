<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarsGiftOptions (crc32 d3c96bc8), returns Vector<StarsGiftOption>. */
final class TlPaymentsGetStarsGiftOptionsData extends Data
{
    public const METHOD = 'payments.getStarsGiftOptions';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $userId,
    ) {
    }
}
