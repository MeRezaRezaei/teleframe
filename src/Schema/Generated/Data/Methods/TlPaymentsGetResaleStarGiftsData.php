<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getResaleStarGifts (crc32 7a5fa236), returns payments.ResaleStarGifts. */
final class TlPaymentsGetResaleStarGiftsData extends Data
{
    public const METHOD = 'payments.getResaleStarGifts';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $sortByPrice,
    public ?bool $sortByNum,
    public ?bool $forCraft,
    public ?bool $starsOnly,
    public ?int $attributesHash,
    public int $giftId,
    public ?array $attributes,
    public string $offset,
    public int $limit,
    ) {
    }
}
