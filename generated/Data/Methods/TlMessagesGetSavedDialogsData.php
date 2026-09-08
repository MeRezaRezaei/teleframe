<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSavedDialogs (crc32 1e91fc99), returns messages.SavedDialogs. */
final class TlMessagesGetSavedDialogsData extends Data
{
    public const METHOD = 'messages.getSavedDialogs';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $excludePinned,
    public mixed $parentPeer,
    public int $offsetDate,
    public int $offsetId,
    public mixed $offsetPeer,
    public int $limit,
    public int $hash,
    ) {
    }
}
