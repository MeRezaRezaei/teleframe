<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlGlobalPrivacySettingsGlobalPrivacySettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type DisallowedGiftsSettings (spec §4.1). */
final class TlDisallowedGiftsSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_disallowed_gifts_settings';

    protected $guarded = [];

    public function disallowedGifts(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'disallowed_gifts');
    }
    public function disallowedGiftsGlobalPrivacySettings(): HasMany
    {
        return $this->hasMany(TlGlobalPrivacySettingsGlobalPrivacySettings::class, 'disallowed_gifts');
    }
}
