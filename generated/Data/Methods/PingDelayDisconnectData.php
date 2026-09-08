<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method ping_delay_disconnect (crc32 f3427b8c), returns Pong. */
final class PingDelayDisconnectData extends Data
{
    public const METHOD = 'ping_delay_disconnect';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $pingId,
    public int $disconnectDelay,
    ) {
    }
}
