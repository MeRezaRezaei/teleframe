<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method aicompose.getToneExample (crc32 d1b4ab14), returns AiComposeToneExample. */
final class TlAicomposeGetToneExampleData extends Data
{
    public const METHOD = 'aicompose.getToneExample';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $tone,
    public int $num,
    ) {
    }
}
