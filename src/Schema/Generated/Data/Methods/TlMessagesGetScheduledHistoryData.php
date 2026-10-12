<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getScheduledHistory (crc32 f516760b), returns messages.Messages. */
final class TlMessagesGetScheduledHistoryData extends Data
{
    public const METHOD = 'messages.getScheduledHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $hash,
    ) {
    }
}
