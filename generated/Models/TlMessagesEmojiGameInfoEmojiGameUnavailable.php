<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messages.emojiGameUnavailable of messages.EmojiGameInfo (crc32 59e65335). */
final class TlMessagesEmojiGameInfoEmojiGameUnavailable extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_emoji_game_info_emoji_game_unavailable';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
