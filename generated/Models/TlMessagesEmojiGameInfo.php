<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEmojiGameInfo;

/** Anchor model for TL type messages.EmojiGameInfo (spec §4.1). */
final class TlMessagesEmojiGameInfo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_emoji_game_info';

    protected $guarded = [];

    public function info(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateEmojiGameInfo::class, 'info');
    }
}
