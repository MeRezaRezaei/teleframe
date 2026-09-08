<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.setHistoryTTL (crc32 b80e5fe4), returns Updates. */
final class TlMessagesSetHistoryTTLData extends Data
{
    public const METHOD = 'messages.setHistoryTTL';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $period,
    ) {
    }
}
