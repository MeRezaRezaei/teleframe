<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDraftMessageDraftMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageRichMessageDraftAction;

/** Anchor model for TL type RichMessage (spec §4.1). */
final class TlRichMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_rich_message';

    protected $guarded = [];

    public function richMessage(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'rich_message');
    }
    public function richMessageBotInlineMessageRichMessage(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageRichMessage::class, 'rich_message');
    }
    public function richMessageDraftMessage(): HasMany
    {
        return $this->hasMany(TlDraftMessageDraftMessage::class, 'rich_message');
    }
    public function richMessageSendMessageRichMessageDraftAction(): HasMany
    {
        return $this->hasMany(TlSendMessageActionSendMessageRichMessageDraftAction::class, 'rich_message');
    }
}
