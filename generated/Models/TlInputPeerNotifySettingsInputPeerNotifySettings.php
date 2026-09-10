<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBool;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlNotificationSound;

/** Constructor model for inputPeerNotifySettings of InputPeerNotifySettings (crc32 cacb6ae2). */
final class TlInputPeerNotifySettingsInputPeerNotifySettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_peer_notify_settings_input_peer_notify_settings';

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
    public function sound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'sound');
    }
    public function storiesMuted(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'stories_muted');
    }
    public function storiesHideSender(): BelongsTo
    {
        return $this->belongsTo(TlBool::class, 'stories_hide_sender');
    }
    public function storiesSound(): BelongsTo
    {
        return $this->belongsTo(TlNotificationSound::class, 'stories_sound');
    }
}
