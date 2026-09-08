<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for emojiGroupPremium of EmojiGroup.
 */
final class EmojiGroupPremiumData extends TlEmojiGroupAbstractData
{
    public function __construct(
    public string $title,
    public int $iconEmojiId,
    ) {
    }
}
