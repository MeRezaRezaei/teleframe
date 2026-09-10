<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotInlineSend;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateInlineBotCallbackQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebViewMessageSentWebViewMessageSent;

/** Anchor model for TL type InputBotInlineMessageID (spec §4.1). */
final class TlInputBotInlineMessageID extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_bot_inline_message_i_d_input_bot_inl_65be0b9b7598';

    protected $guarded = [];

    public function msgId(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotInlineSend::class, 'msg_id');
    }
    public function msgIdUpdateInlineBotCallbackQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateInlineBotCallbackQuery::class, 'msg_id');
    }
    public function msgIdWebViewMessageSent(): HasMany
    {
        return $this->hasMany(TlWebViewMessageSentWebViewMessageSent::class, 'msg_id');
    }
}
