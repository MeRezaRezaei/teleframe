<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setDefaultHistoryTTL (crc32 9eb51445), returns Bool. */
final class TlMessagesSetDefaultHistoryTTLData extends Data
{
    public const METHOD = 'messages.setDefaultHistoryTTL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $period,
    ) {
    }
}
