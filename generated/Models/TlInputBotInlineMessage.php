<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultGame;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineResultInputBotInlineResultPhoto;

/** Anchor model for TL type InputBotInlineMessage (spec §4.1). */
final class TlInputBotInlineMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_input_bot_inline_message_game';

    protected $guarded = [];

    public function sendMessage(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResult::class, 'send_message');
    }
    public function sendMessageInputBotInlineResultDocument(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResultDocument::class, 'send_message');
    }
    public function sendMessageInputBotInlineResultGame(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResultGame::class, 'send_message');
    }
    public function sendMessageInputBotInlineResultPhoto(): HasMany
    {
        return $this->hasMany(TlInputBotInlineResultInputBotInlineResultPhoto::class, 'send_message');
    }
}
