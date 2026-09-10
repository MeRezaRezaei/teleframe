<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAiComposeToneExampleAiComposeToneExample;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatlistsChatlistInviteChatlistInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilter;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogFilterDialogFilterChatlist;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFactCheckFactCheck;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGroupCallMessageGroupCallMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputContactInputPhoneContact;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoicePremiumGiftStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStarGiftAuctionBid;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftCode;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionGiftPremium;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesComposedMessageWithAIComposedMessageWithAI;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsCheckCanSendGiftResultCheckCanSendGiftResultFail;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPendingSuggestionPendingSuggestion;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerInputPollAnswer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerPollAnswer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedStarGiftSavedStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSendMessageActionSendMessageTextDraftAction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeOriginalDetails;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoItemTodoItem;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTodoListTodoList;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type TextWithEntities (spec §4.1). */
final class TlTextWithEntities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_text_with_entities_text_with_entities';

    protected $guarded = [];

    public function description(): HasMany
    {
        return $this->hasMany(TlPendingSuggestionPendingSuggestion::class, 'description');
    }
    public function diffText(): HasMany
    {
        return $this->hasMany(TlMessagesComposedMessageWithAIComposedMessageWithAI::class, 'diff_text');
    }
    public function from(): HasMany
    {
        return $this->hasMany(TlAiComposeToneExampleAiComposeToneExample::class, 'tl_from');
    }
    public function message(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionGiftPremium::class, 'message');
    }
    public function messageGroupCallMessage(): HasMany
    {
        return $this->hasMany(TlGroupCallMessageGroupCallMessage::class, 'message');
    }
    public function messageInputInvoicePremiumGiftStars(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoicePremiumGiftStars::class, 'message');
    }
    public function messageInputInvoiceStarGift(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStarGift::class, 'message');
    }
    public function messageInputInvoiceStarGiftAuctionBid(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStarGiftAuctionBid::class, 'message');
    }
    public function messageInputStorePaymentPremiumGiftCode(): HasMany
    {
        return $this->hasMany(TlInputStorePaymentPurposeInputStorePaymentPremiumGiftCode::class, 'message');
    }
    public function messageMessageActionGiftCode(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionGiftCode::class, 'message');
    }
    public function messageMessageActionStarGift(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionStarGift::class, 'message');
    }
    public function messageSavedStarGift(): HasMany
    {
        return $this->hasMany(TlSavedStarGiftSavedStarGift::class, 'message');
    }
    public function messageStarGiftAttributeOriginalDetails(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributeOriginalDetails::class, 'message');
    }
    public function messageStarGiftAuctionAcquiredGift(): HasMany
    {
        return $this->hasMany(TlStarGiftAuctionAcquiredGiftStarGiftAuctionAcquiredGift::class, 'message');
    }
    public function note(): HasMany
    {
        return $this->hasMany(TlInputContactInputPhoneContact::class, 'note');
    }
    public function noteUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'note');
    }
    public function question(): HasMany
    {
        return $this->hasMany(TlPollPoll::class, 'question');
    }
    public function reason(): HasMany
    {
        return $this->hasMany(TlPaymentsCheckCanSendGiftResultCheckCanSendGiftResultFail::class, 'reason');
    }
    public function resultText(): HasMany
    {
        return $this->hasMany(TlMessagesComposedMessageWithAIComposedMessageWithAI::class, 'result_text');
    }
    public function text(): HasMany
    {
        return $this->hasMany(TlSendMessageActionSendMessageTextDraftAction::class, 'text');
    }
    public function textFactCheck(): HasMany
    {
        return $this->hasMany(TlFactCheckFactCheck::class, 'text');
    }
    public function textInputPollAnswer(): HasMany
    {
        return $this->hasMany(TlPollAnswerInputPollAnswer::class, 'text');
    }
    public function textPollAnswer(): HasMany
    {
        return $this->hasMany(TlPollAnswerPollAnswer::class, 'text');
    }
    public function title(): HasMany
    {
        return $this->hasMany(TlDialogFilterDialogFilter::class, 'title');
    }
    public function titleChatlistsChatlistInvite(): HasMany
    {
        return $this->hasMany(TlChatlistsChatlistInviteChatlistInvite::class, 'title');
    }
    public function titleDialogFilterChatlist(): HasMany
    {
        return $this->hasMany(TlDialogFilterDialogFilterChatlist::class, 'title');
    }
    public function titlePendingSuggestion(): HasMany
    {
        return $this->hasMany(TlPendingSuggestionPendingSuggestion::class, 'title');
    }
    public function titleTodoItem(): HasMany
    {
        return $this->hasMany(TlTodoItemTodoItem::class, 'title');
    }
    public function titleTodoList(): HasMany
    {
        return $this->hasMany(TlTodoListTodoList::class, 'title');
    }
    public function to(): HasMany
    {
        return $this->hasMany(TlAiComposeToneExampleAiComposeToneExample::class, 'tl_to');
    }
}
