<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotPreviewMediaBotPreviewMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageExtendedMediaMessageExtendedMedia;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageReplyHeaderMessageReplyHeader;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesWebPagePreviewWebPagePreview;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollAnswerPollAnswer;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPollResultsPollResults;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSponsoredMessageSponsoredMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateServiceNotification;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShortSentMessage;

/** Anchor model for TL type MessageMedia (spec §4.1). */
final class TlMessageMedia extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_media';

    protected $guarded = [];

    public function attachedMedia(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaPoll::class, 'attached_media');
    }
    public function media(): HasMany
    {
        return $this->hasMany(TlMessageMessage::class, 'media');
    }
    public function mediaBotPreviewMedia(): HasMany
    {
        return $this->hasMany(TlBotPreviewMediaBotPreviewMedia::class, 'media');
    }
    public function mediaMessageExtendedMedia(): HasMany
    {
        return $this->hasMany(TlMessageExtendedMediaMessageExtendedMedia::class, 'media');
    }
    public function mediaMessagesWebPagePreview(): HasMany
    {
        return $this->hasMany(TlMessagesWebPagePreviewWebPagePreview::class, 'media');
    }
    public function mediaPollAnswer(): HasMany
    {
        return $this->hasMany(TlPollAnswerPollAnswer::class, 'media');
    }
    public function mediaSponsoredMessage(): HasMany
    {
        return $this->hasMany(TlSponsoredMessageSponsoredMessage::class, 'media');
    }
    public function mediaStoryItem(): HasMany
    {
        return $this->hasMany(TlStoryItemStoryItem::class, 'media');
    }
    public function mediaUpdateServiceNotification(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateServiceNotification::class, 'media');
    }
    public function mediaUpdateShortSentMessage(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShortSentMessage::class, 'media');
    }
    public function replyMedia(): HasMany
    {
        return $this->hasMany(TlMessageReplyHeaderMessageReplyHeader::class, 'reply_media');
    }
    public function solutionMedia(): HasMany
    {
        return $this->hasMany(TlPollResultsPollResults::class, 'solution_media');
    }
}
