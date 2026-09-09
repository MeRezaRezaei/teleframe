<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlNotificationSound;

/** Constructor model for peerNotifySettings of PeerNotifySettings (crc32 99622c0c). */
final class TlPeerNotifySettingsPeerNotifySettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_peer_notify_settings_peer_notify_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'mute_until' => 'int',
    ];

    public function showPreviews(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'show_previews');
    }
    public function silent(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'silent');
    }
    public function iosSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'ios_sound');
    }
    public function androidSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'android_sound');
    }
    public function otherSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'other_sound');
    }
    public function storiesMuted(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'stories_muted');
    }
    public function storiesHideSender(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'stories_hide_sender');
    }
    public function storiesIosSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'stories_ios_sound');
    }
    public function storiesAndroidSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'stories_android_sound');
    }
    public function storiesOtherSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'stories_other_sound');
    }
}
