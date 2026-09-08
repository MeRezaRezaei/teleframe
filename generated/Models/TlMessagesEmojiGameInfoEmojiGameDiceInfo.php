<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesEmojiGameInfoEmojiGameDiceInfoParams;

/** Constructor model for messages.emojiGameDiceInfo of messages.EmojiGameInfo (crc32 44e56023). */
final class TlMessagesEmojiGameInfoEmojiGameDiceInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_emoji_game_info_emoji_game_dice_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'game_hash' => 'string',
        'prev_stake' => 'int',
        'current_streak' => 'int',
        'plays_left' => 'int',
    ];

    public function params(): HasMany
    {
        return $this->tlChild(TlMessagesEmojiGameInfoEmojiGameDiceInfoParams::class);
    }
}
