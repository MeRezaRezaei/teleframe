<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionInputSendMessageRichMessageDraftAction;

/** Anchor model for TL type InputRichMessage (spec §4.1). */
final class TlInputRichMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_rich_message_input_rich_message';

    protected $guarded = [];

    public function richMessage(): HasMany
    {
        return $this->hasMany(TlSendMessageActionInputSendMessageRichMessageDraftAction::class, 'rich_message');
    }
    public function richMessageInputBotInlineMessageRichMessage(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageRichMessage::class, 'rich_message');
    }
}
