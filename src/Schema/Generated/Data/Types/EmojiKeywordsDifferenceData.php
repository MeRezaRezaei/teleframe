<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiKeywordsDifference of EmojiKeywordsDifference.
 */
final class EmojiKeywordsDifferenceData extends TlEmojiKeywordsDifferenceAbstractData
{
    public function __construct(
    public string $langCode,
    public int $fromVersion,
    public int $version,
    public array $keywords,
    ) {
    }
}
