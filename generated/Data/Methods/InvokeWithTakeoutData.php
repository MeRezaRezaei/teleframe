<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithTakeout (crc32 aca9fd2e), returns X. */
final class InvokeWithTakeoutData extends Data
{
    public const METHOD = 'invokeWithTakeout';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public int $takeoutId,
    public mixed $query,
    ) {
    }
}
