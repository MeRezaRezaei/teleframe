<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithoutUpdates (crc32 bf9459b7), returns X. */
final class InvokeWithoutUpdatesData extends Data
{
    public const METHOD = 'invokeWithoutUpdates';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public mixed $query,
    ) {
    }
}
