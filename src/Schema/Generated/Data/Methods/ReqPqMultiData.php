<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method req_pq_multi (crc32 be7e8ef1), returns ResPQ. */
final class ReqPqMultiData extends Data
{
    public const METHOD = 'req_pq_multi';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public string $nonce,
    ) {
    }
}
