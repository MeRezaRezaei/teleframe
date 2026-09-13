<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method req_pq (crc32 60469778), returns ResPQ. */
final class ReqPqData extends Data
{
    public const METHOD = 'req_pq';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $nonce,
    ) {
    }
}
