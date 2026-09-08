<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for peerNotifySettings of PeerNotifySettings (crc32 99622c0c). */
final class TlPeerNotifySettingsPeerNotifySettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_peer_notify_settings_peer_notify_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'show_previews' => 'string',
        'silent' => 'string',
        'mute_until' => 'int',
        'ios_sound' => 'string',
        'android_sound' => 'string',
        'other_sound' => 'string',
        'stories_muted' => 'string',
        'stories_hide_sender' => 'string',
        'stories_ios_sound' => 'string',
        'stories_android_sound' => 'string',
        'stories_other_sound' => 'string',
    ];
}
