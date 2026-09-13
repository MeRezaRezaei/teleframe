<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method aicompose.saveTone (crc32 1782cbb1), returns Bool. */
final class TlAicomposeSaveToneData extends Data
{
    public const METHOD = 'aicompose.saveTone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $tone,
    public mixed $unsave,
    ) {
    }
}
