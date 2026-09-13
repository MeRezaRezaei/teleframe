<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getDefaultHistoryTTL (crc32 658b7188), returns DefaultHistoryTTL. */
final class TlMessagesGetDefaultHistoryTTLData extends Data
{
    public const METHOD = 'messages.getDefaultHistoryTTL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    ) {
    }
}
