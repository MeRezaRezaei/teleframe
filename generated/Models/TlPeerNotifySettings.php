<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChatFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDialogDialog;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlForumTopicForumTopic;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNotifySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type PeerNotifySettings (spec §4.1). */
final class TlPeerNotifySettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_peer_notify_settings';

    protected $guarded = [];

    public function notifySettings(): HasMany
    {
        return $this->hasMany(TlChatFullChatFull::class, 'notify_settings');
    }
    public function notifySettingsChannelFull(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'notify_settings');
    }
    public function notifySettingsDialog(): HasMany
    {
        return $this->hasMany(TlDialogDialog::class, 'notify_settings');
    }
    public function notifySettingsForumTopic(): HasMany
    {
        return $this->hasMany(TlForumTopicForumTopic::class, 'notify_settings');
    }
    public function notifySettingsUpdateNotifySettings(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNotifySettings::class, 'notify_settings');
    }
    public function notifySettingsUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'notify_settings');
    }
}
