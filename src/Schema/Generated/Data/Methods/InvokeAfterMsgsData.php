<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method invokeAfterMsgs (crc32 3dc4b4f0), returns X. */
final class InvokeAfterMsgsData extends Data
{
    public const METHOD = 'invokeAfterMsgs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed ${X,
    public array $msgIds,
    public mixed $query,
    ) {
    }
}
