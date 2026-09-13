<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Methods;

use Spatie\LaravelData\Data;

/** Request DTO for RPC method messages.searchStickerSets (crc32 35705b8a), returns messages.FoundStickerSets. */
final class TlMessagesSearchStickerSetsData extends Data
{
    public const METHOD = 'messages.searchStickerSets';

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
