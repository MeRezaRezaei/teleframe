<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAntiSpam;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslation;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleForum;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleInvites;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionTogglePreHistoryHidden;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatureProfiles;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlCodeSettingsCodeSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConfigConfig;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlContactContact;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerNotifySettingsInputPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlJSONValueJsonBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlKeyboardButtonKeyboardButtonRequestPoll;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionNoForwardsRequest;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionNoForwardsToggle;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionTopicEdit;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedStarGiftsSavedStarGifts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettingsPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionsNotifySettingsReactionsNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeBroadcast;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeChat;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRequestPeerTypeRequestPeerTypeUser;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotStopped;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChannelViewForumAsMessages;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateChatParticipantAdmin;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateWebBrowserException;

/** Anchor model for TL type Bool (spec §4.1). */
final class TlBool extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bool';

    protected $guarded = [];

    public function appSandbox(): HasMany
    {
        return $this->hasMany(TlCodeSettingsCodeSettings::class, 'app_sandbox');
    }
    public function bot(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeUser::class, 'bot');
    }
    public function chatNotificationsEnabled(): HasMany
    {
        return $this->hasMany(TlPaymentsSavedStarGiftsSavedStarGifts::class, 'chat_notifications_enabled');
    }
    public function closed(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionTopicEdit::class, 'closed');
    }
    public function enabled(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChannelViewForumAsMessages::class, 'enabled');
    }
    public function forum(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeChat::class, 'forum');
    }
    public function hasUsername(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeChat::class, 'has_username');
    }
    public function hasUsernameRequestPeerTypeBroadcast(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeBroadcast::class, 'has_username');
    }
    public function hidden(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionTopicEdit::class, 'hidden');
    }
    public function isAdmin(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateChatParticipantAdmin::class, 'is_admin');
    }
    public function joinMuted(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleGroupCallSetting::class, 'join_muted');
    }
    public function mutual(): HasMany
    {
        return $this->hasMany(TlContactContact::class, 'mutual');
    }
    public function newValue(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionNoForwardsToggle::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleAntiSpam(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAntiSpam::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleAutotranslation(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleAutotranslation::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleForum(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleForum::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleInvites(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleInvites::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleNoForwards(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleNoForwards::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionTogglePreHistoryHidden(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionTogglePreHistoryHidden::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleSignatureProfiles(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatureProfiles::class, 'new_value');
    }
    public function newValueChannelAdminLogEventActionToggleSignatures(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionToggleSignatures::class, 'new_value');
    }
    public function newValueMessageActionNoForwardsRequest(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionNoForwardsRequest::class, 'new_value');
    }
    public function openExternalBrowser(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateWebBrowserException::class, 'open_external_browser');
    }
    public function premium(): HasMany
    {
        return $this->hasMany(TlRequestPeerTypeRequestPeerTypeUser::class, 'premium');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionNoForwardsToggle::class, 'prev_value');
    }
    public function prevValueMessageActionNoForwardsRequest(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionNoForwardsRequest::class, 'prev_value');
    }
    public function quiz(): HasMany
    {
        return $this->hasMany(TlKeyboardButtonKeyboardButtonRequestPoll::class, 'quiz');
    }
    public function showPreviews(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'show_previews');
    }
    public function showPreviewsPeerNotifySettings(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'show_previews');
    }
    public function showPreviewsReactionsNotifySettings(): HasMany
    {
        return $this->hasMany(TlReactionsNotifySettingsReactionsNotifySettings::class, 'show_previews');
    }
    public function silent(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'silent');
    }
    public function silentPeerNotifySettings(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'silent');
    }
    public function stopped(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotStopped::class, 'stopped');
    }
    public function storiesHideSender(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'stories_hide_sender');
    }
    public function storiesHideSenderPeerNotifySettings(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'stories_hide_sender');
    }
    public function storiesMuted(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'stories_muted');
    }
    public function storiesMutedPeerNotifySettings(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'stories_muted');
    }
    public function testMode(): HasMany
    {
        return $this->hasMany(TlConfigConfig::class, 'test_mode');
    }
    public function value(): HasMany
    {
        return $this->hasMany(TlJSONValueJsonBool::class, 'tl_value');
    }
}
