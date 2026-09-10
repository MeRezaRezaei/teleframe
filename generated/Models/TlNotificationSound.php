<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputPeerNotifySettingsInputPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerNotifySettingsPeerNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionsNotifySettingsReactionsNotifySettings;

/** Anchor model for TL type NotificationSound (spec §4.1). */
final class TlNotificationSound extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_notification_sound_notification_sound_default';

    protected $guarded = [];

    public function androidSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'android_sound');
    }
    public function iosSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'ios_sound');
    }
    public function otherSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'other_sound');
    }
    public function sound(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'sound');
    }
    public function soundReactionsNotifySettings(): HasMany
    {
        return $this->hasMany(TlReactionsNotifySettingsReactionsNotifySettings::class, 'sound');
    }
    public function storiesAndroidSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'stories_android_sound');
    }
    public function storiesIosSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'stories_ios_sound');
    }
    public function storiesOtherSound(): HasMany
    {
        return $this->hasMany(TlPeerNotifySettingsPeerNotifySettings::class, 'stories_other_sound');
    }
    public function storiesSound(): HasMany
    {
        return $this->hasMany(TlInputPeerNotifySettingsInputPeerNotifySettings::class, 'stories_sound');
    }
}
