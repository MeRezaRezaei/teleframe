<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method premium.applyBoost (crc32 6b7da746), returns premium.MyBoosts. */
final class TlPremiumApplyBoostData extends Data
{
    public const METHOD = 'premium.applyBoost';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?array $slots,
    public mixed $peer,
    ) {
    }
}
