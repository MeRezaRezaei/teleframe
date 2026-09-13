<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.emojiGroups of messages.EmojiGroups.
 */
final class TlMessagesEmojiGroupsData extends TlMessagesEmojiGroupsAbstractData
{
    public function __construct(
    public int $hash,
    public array $groups,
    ) {
    }
}
