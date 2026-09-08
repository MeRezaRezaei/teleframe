<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method fragment.getCollectibleInfo (crc32 be1e85ba), returns fragment.CollectibleInfo. */
final class TlFragmentGetCollectibleInfoData extends Data
{
    public const METHOD = 'fragment.getCollectibleInfo';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $collectible,
    ) {
    }
}
