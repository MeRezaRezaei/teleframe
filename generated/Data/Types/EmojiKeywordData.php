<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiKeyword of EmojiKeyword.
 */
final class EmojiKeywordData extends TlEmojiKeywordAbstractData
{
    public function __construct(
    public string $keyword,
    public array $emoticons,
    ) {
    }
}
