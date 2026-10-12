<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiStatusCollectible of EmojiStatus.
 */
final class EmojiStatusCollectibleData extends TlEmojiStatusAbstractData
{
    public function __construct(
    public int $flags,
    public int $collectibleId,
    public int $documentId,
    public string $title,
    public string $slug,
    public int $patternDocumentId,
    public int $centerColor,
    public int $edgeColor,
    public int $patternColor,
    public int $textColor,
    public ?int $until,
    ) {
    }
}
