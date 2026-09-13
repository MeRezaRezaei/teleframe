<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method premium.getBoostsList (crc32 60f67660), returns premium.BoostsList. */
final class TlPremiumGetBoostsListData extends Data
{
    public const METHOD = 'premium.getBoostsList';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $gifts,
    public mixed $peer,
    public string $offset,
    public int $limit,
    ) {
    }
}
