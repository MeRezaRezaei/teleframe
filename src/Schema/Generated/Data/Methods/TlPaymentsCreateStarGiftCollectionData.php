<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.createStarGiftCollection (crc32 1f4a0e87), returns StarGiftCollection. */
final class TlPaymentsCreateStarGiftCollectionData extends Data
{
    public const METHOD = 'payments.createStarGiftCollection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public string $title,
    public array $stargift,
    ) {
    }
}
