<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerSettingsPeerSettingsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerSettingsPeerSettingsUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerSettings;

/** Constructor model for messages.peerSettings of messages.PeerSettings (crc32 6880b94d). */
final class TlMessagesPeerSettingsPeerSettings extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_messages_peer_settings_peer_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesPeerSettingsPeerSettingsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesPeerSettingsPeerSettingsUsers::class);
    }

    public function settings(): BelongsTo
    {
        return $this->belongsTo(TlPeerSettings::class, 'settings');
    }
}
