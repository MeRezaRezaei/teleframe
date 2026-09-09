<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messages.emojiGameOutcome of messages.EmojiGameOutcome (crc32 da2ad647). */
final class TlMessagesEmojiGameOutcomeEmojiGameOutcome extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_emoji_game_outcome_emoji_game_outcome';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'seed' => 'string',
        'stake_ton_amount' => 'int',
        'ton_amount' => 'int',
    ];
}
