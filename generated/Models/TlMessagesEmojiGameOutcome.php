<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDice;

/** Anchor model for TL type messages.EmojiGameOutcome (spec §4.1). */
final class TlMessagesEmojiGameOutcome extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_emoji_game_outcome_emoji_game_outcome';

    protected $guarded = [];

    public function gameOutcome(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaDice::class, 'game_outcome');
    }
}
