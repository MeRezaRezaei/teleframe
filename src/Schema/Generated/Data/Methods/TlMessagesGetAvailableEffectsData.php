<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getAvailableEffects (crc32 dea20a39), returns messages.AvailableEffects. */
final class TlMessagesGetAvailableEffectsData extends Data
{
    public const METHOD = 'messages.getAvailableEffects';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $hash,
    ) {
    }
}
