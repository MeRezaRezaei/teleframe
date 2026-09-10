<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionDeleteMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionStopPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinned;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageCopyMsgCopy;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPublicForwardPublicForwardMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReactionPublicForward;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewStoryViewPublicForward;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotEditBusinessMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotGuestChatQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotNewBusinessMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBusinessBotCallbackQuery;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditChannelMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateEditMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewChannelMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewScheduledMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateQuickReplyMessage;

/** Anchor model for TL type Message (spec §4.1). */
final class TlMessage extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_message';

    protected $guarded = [];

    public function message(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewMessage::class, 'message');
    }
    public function messageChannelAdminLogEventActionDeleteMessage(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionDeleteMessage::class, 'message');
    }
    public function messageChannelAdminLogEventActionSendMessage(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionSendMessage::class, 'message');
    }
    public function messageChannelAdminLogEventActionStopPoll(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionStopPoll::class, 'message');
    }
    public function messageChannelAdminLogEventActionUpdatePinned(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionUpdatePinned::class, 'message');
    }
    public function messagePublicForwardMessage(): HasMany
    {
        return $this->hasMany(TlPublicForwardPublicForwardMessage::class, 'message');
    }
    public function messageStoryReactionPublicForward(): HasMany
    {
        return $this->hasMany(TlStoryReactionStoryReactionPublicForward::class, 'message');
    }
    public function messageStoryViewPublicForward(): HasMany
    {
        return $this->hasMany(TlStoryViewStoryViewPublicForward::class, 'message');
    }
    public function messageUpdateBotEditBusinessMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotEditBusinessMessage::class, 'message');
    }
    public function messageUpdateBotGuestChatQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotGuestChatQuery::class, 'message');
    }
    public function messageUpdateBotNewBusinessMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotNewBusinessMessage::class, 'message');
    }
    public function messageUpdateBusinessBotCallbackQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBusinessBotCallbackQuery::class, 'message');
    }
    public function messageUpdateEditChannelMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateEditChannelMessage::class, 'message');
    }
    public function messageUpdateEditMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateEditMessage::class, 'message');
    }
    public function messageUpdateNewChannelMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewChannelMessage::class, 'message');
    }
    public function messageUpdateNewScheduledMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewScheduledMessage::class, 'message');
    }
    public function messageUpdateQuickReplyMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateQuickReplyMessage::class, 'message');
    }
    public function newMessage(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage::class, 'new_message');
    }
    public function origMessage(): HasMany
    {
        return $this->hasMany(TlMessageCopyMsgCopy::class, 'orig_message');
    }
    public function prevMessage(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionEditMessage::class, 'prev_message');
    }
    public function replyToMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotNewBusinessMessage::class, 'reply_to_message');
    }
    public function replyToMessageUpdateBotEditBusinessMessage(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotEditBusinessMessage::class, 'reply_to_message');
    }
    public function replyToMessageUpdateBusinessBotCallbackQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBusinessBotCallbackQuery::class, 'reply_to_message');
    }
}
