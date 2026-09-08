<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method aicompose.deleteTone (crc32 dd39316a), returns Bool. */
final class TlAicomposeDeleteToneData extends Data
{
    public const METHOD = 'aicompose.deleteTone';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $tone,
    ) {
    }
}
