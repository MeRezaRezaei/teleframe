<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerSettingsPeerSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePeerSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type PeerSettings (spec §4.1). */
final class TlPeerSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_peer_settings';

    protected $guarded = [];

    public function settings(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'settings');
    }
    public function settingsMessagesPeerSettings(): HasMany
    {
        return $this->hasMany(TlMessagesPeerSettingsPeerSettings::class, 'settings');
    }
    public function settingsUpdatePeerSettings(): HasMany
    {
        return $this->hasMany(TlUpdateUpdatePeerSettings::class, 'settings');
    }
}
