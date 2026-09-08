<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesEmojiGroupsEmojiGroupsGroups;

/** Constructor model for messages.emojiGroups of messages.EmojiGroups (crc32 881fb94b). */
final class TlMessagesEmojiGroupsEmojiGroups extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_emoji_groups_emoji_groups';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'hash' => 'int',
    ];

    public function groups(): HasMany
    {
        return $this->tlChild(TlMessagesEmojiGroupsEmojiGroupsGroups::class);
    }
}
