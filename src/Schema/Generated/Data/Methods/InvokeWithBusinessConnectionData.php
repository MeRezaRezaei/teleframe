<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeWithBusinessConnection (crc32 dd289f8e), returns X. */
final class InvokeWithBusinessConnectionData extends Data
{
    public const METHOD = 'invokeWithBusinessConnection';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public string $connectionId,
    public mixed $query,
    ) {
    }
}
