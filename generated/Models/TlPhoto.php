<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotAppBotApp;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineMediaResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatInviteChatInvite;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGameGame;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionChatEditPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSuggestProfilePhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPhotosPhotoPhoto;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestedPeerRequestedPeerChannel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestedPeerRequestedPeerChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestedPeerRequestedPeerUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSponsoredMessageSponsoredMessage;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryAlbumStoryAlbum;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageWebPage;

/** Anchor model for TL type Photo (spec §4.1). */
final class TlPhoto extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_photo_photo';

    protected $guarded = [];

    public function chatPhoto(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'chat_photo');
    }
    public function chatPhotoChannelFull(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'chat_photo');
    }
    public function descriptionPhoto(): HasMany
    {
        return $this->hasMany(TlBotInfoBotInfo::class, 'description_photo');
    }
    public function fallbackPhoto(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'fallback_photo');
    }
    public function iconPhoto(): HasMany
    {
        return $this->hasMany(TlStoryAlbumStoryAlbum::class, 'icon_photo');
    }
    public function newPhoto(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto::class, 'new_photo');
    }
    public function personalPhoto(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'personal_photo');
    }
    public function photo(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaPhoto::class, 'photo');
    }
    public function photoBotApp(): HasMany
    {
        return $this->hasMany(TlBotAppBotApp::class, 'photo');
    }
    public function photoBotInlineMediaResult(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineMediaResult::class, 'photo');
    }
    public function photoChatInvite(): HasMany
    {
        return $this->hasMany(TlChatInviteChatInvite::class, 'photo');
    }
    public function photoGame(): HasMany
    {
        return $this->hasMany(TlGameGame::class, 'photo');
    }
    public function photoMessageActionChatEditPhoto(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionChatEditPhoto::class, 'photo');
    }
    public function photoMessageActionSuggestProfilePhoto(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSuggestProfilePhoto::class, 'photo');
    }
    public function photoPhotosPhoto(): HasMany
    {
        return $this->hasMany(TlPhotosPhotoPhoto::class, 'photo');
    }
    public function photoRequestedPeerChannel(): HasMany
    {
        return $this->hasMany(TlRequestedPeerRequestedPeerChannel::class, 'photo');
    }
    public function photoRequestedPeerChat(): HasMany
    {
        return $this->hasMany(TlRequestedPeerRequestedPeerChat::class, 'photo');
    }
    public function photoRequestedPeerUser(): HasMany
    {
        return $this->hasMany(TlRequestedPeerRequestedPeerUser::class, 'photo');
    }
    public function photoSponsoredMessage(): HasMany
    {
        return $this->hasMany(TlSponsoredMessageSponsoredMessage::class, 'photo');
    }
    public function photoWebPage(): HasMany
    {
        return $this->hasMany(TlWebPageWebPage::class, 'photo');
    }
    public function prevPhoto(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangePhoto::class, 'prev_photo');
    }
    public function profilePhoto(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'profile_photo');
    }
    public function videoCover(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaDocument::class, 'video_cover');
    }
}
