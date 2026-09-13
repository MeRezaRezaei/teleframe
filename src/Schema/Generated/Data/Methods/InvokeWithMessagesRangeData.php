<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithMessagesRange (crc32 365275f2), returns X. */
final class InvokeWithMessagesRangeData extends Data
{
    public const METHOD = 'invokeWithMessagesRange';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public mixed $range,
    public mixed $query,
    ) {
    }
}
