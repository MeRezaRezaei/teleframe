<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.deleteSavedHistory (crc32 4dc5085f), returns messages.AffectedHistory. */
final class TlMessagesDeleteSavedHistoryData extends Data
{
    public const METHOD = 'messages.deleteSavedHistory';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $parentPeer,
    public mixed $peer,
    public int $maxId,
    public ?int $minDate,
    public ?int $maxDate,
    ) {
    }
}
