<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchEmojiStickerSets (crc32 92b4494c), returns messages.FoundStickerSets. */
final class TlMessagesSearchEmojiStickerSetsData extends Data
{
    public const METHOD = 'messages.searchEmojiStickerSets';

    public static function method(): string
    {
        return self::METHOD;
    }

    public function __construct(
    public int $flags,
    public ?bool $excludeFeatured,
    public string $q,
    public int $hash,
    ) {
    }
}
