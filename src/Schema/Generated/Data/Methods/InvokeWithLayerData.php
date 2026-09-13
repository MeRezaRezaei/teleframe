<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithLayer (crc32 da9b0d0d), returns X. */
final class InvokeWithLayerData extends Data
{
    public const METHOD = 'invokeWithLayer';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public int $layer,
    public mixed $query,
    ) {
    }
}
