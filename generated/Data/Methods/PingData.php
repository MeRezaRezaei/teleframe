<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method ping (crc32 7abe77ec), returns Pong. */
final class PingData extends Data
{
    public const METHOD = 'ping';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $pingId,
    ) {
    }
}
