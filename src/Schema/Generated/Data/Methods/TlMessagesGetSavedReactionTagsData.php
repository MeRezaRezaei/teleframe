<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.getSavedReactionTags (crc32 3637e05b), returns messages.SavedReactionTags. */
final class TlMessagesGetSavedReactionTagsData extends Data
{
    public const METHOD = 'messages.getSavedReactionTags';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public mixed $peer,
    public int $hash,
    ) {
    }
}
