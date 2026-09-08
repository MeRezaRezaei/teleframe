<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiList of EmojiList.
 */
final class EmojiListData extends TlEmojiListAbstractData
{
    public function __construct(
    public int $hash,
    public array $documentId,
    ) {
    }
}
