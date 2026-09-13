<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method smsjobs.getStatus (crc32 10a698e8), returns smsjobs.Status. */
final class TlSmsjobsGetStatusData extends Data
{
    public const METHOD = 'smsjobs.getStatus';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
