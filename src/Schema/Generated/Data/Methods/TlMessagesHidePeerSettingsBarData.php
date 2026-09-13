<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.hidePeerSettingsBar (crc32 4facb138), returns Bool. */
final class TlMessagesHidePeerSettingsBarData extends Data
{
    public const METHOD = 'messages.hidePeerSettingsBar';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    ) {
    }
}
