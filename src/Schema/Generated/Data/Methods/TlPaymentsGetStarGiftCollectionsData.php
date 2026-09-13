<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method payments.getStarGiftCollections (crc32 981b91dd), returns payments.StarGiftCollections. */
final class TlPaymentsGetStarGiftCollectionsData extends Data
{
    public const METHOD = 'payments.getStarGiftCollections';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $hash,
    ) {
    }
}
