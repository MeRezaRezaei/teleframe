<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaAuto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaContact;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaGeo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaWebPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageText;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageGame;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaAuto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaContact;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaGeo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaVenue;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaWebPage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageRichMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageText;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;

/** Anchor model for TL type ReplyMarkup (spec §4.1). */
final class TlReplyMarkup extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_reply_markup';

    protected $guarded = [];

    public function replyMarkup(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaAuto(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaAuto::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaContact(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaContact::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaGeo(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaGeo::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaInvoice::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaVenue(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaVenue::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageMediaWebPage(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaWebPage::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageRichMessage(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageRichMessage::class, 'reply_markup');
    }
    public function replyMarkupBotInlineMessageText(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageText::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageGame(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageGame::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaAuto(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaAuto::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaContact(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaContact::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaGeo(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaGeo::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaInvoice::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaVenue(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaVenue::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageMediaWebPage(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaWebPage::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageRichMessage(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageRichMessage::class, 'reply_markup');
    }
    public function replyMarkupInputBotInlineMessageText(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageText::class, 'reply_markup');
    }
}
