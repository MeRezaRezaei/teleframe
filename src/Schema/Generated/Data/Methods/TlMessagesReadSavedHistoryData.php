<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.readSavedHistory (crc32 ba4a3b5b), returns Bool. */
final class TlMessagesReadSavedHistoryData extends Data
{
    public const METHOD = 'messages.readSavedHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public mixed $parentPeer,
    public mixed $peer,
    public int $maxId,
    ) {
    }
}
