<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.join (crc32 a74ece2d), returns Bool. */
final class TlSmsjobsJoinData extends Data
{
    public const METHOD = 'smsjobs.join';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
