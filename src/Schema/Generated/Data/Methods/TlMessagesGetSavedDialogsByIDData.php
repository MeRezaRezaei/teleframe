<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSavedDialogsByID (crc32 6f6f9c96), returns messages.SavedDialogs. */
final class TlMessagesGetSavedDialogsByIDData extends Data
{
    public const METHOD = 'messages.getSavedDialogsByID';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $parentPeer,
    public array $ids,
    ) {
    }
}
