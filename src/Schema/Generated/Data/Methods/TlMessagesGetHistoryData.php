<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getHistory (crc32 4423e6c5), returns messages.Messages. */
final class TlMessagesGetHistoryData extends Data
{
    public const METHOD = 'messages.getHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $peer,
    public int $offsetId,
    public int $offsetDate,
    public int $addOffset,
    public int $limit,
    public int $maxId,
    public int $minId,
    public int $hash,
    ) {
    }
}
