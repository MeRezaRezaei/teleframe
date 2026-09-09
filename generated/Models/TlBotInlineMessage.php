<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineMediaResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineResult;

/** Anchor model for TL type BotInlineMessage (spec §4.1). */
final class TlBotInlineMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_inline_message';

    protected $guarded = [];

    public function sendMessage(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineResult::class, 'send_message');
    }
    public function sendMessageBotInlineMediaResult(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineMediaResult::class, 'send_message');
    }
}
