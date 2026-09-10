<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountAutoDownloadSettingsAutoDownloadSettings;

/** Anchor model for TL type AutoDownloadSettings (spec §4.1). */
final class TlAutoDownloadSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_auto_download_settings_auto_download_settings';

    protected $guarded = [];

    public function high(): HasMany
    {
        return $this->hasMany(TlAccountAutoDownloadSettingsAutoDownloadSettings::class, 'high');
    }
    public function low(): HasMany
    {
        return $this->hasMany(TlAccountAutoDownloadSettingsAutoDownloadSettings::class, 'low');
    }
    public function medium(): HasMany
    {
        return $this->hasMany(TlAccountAutoDownloadSettingsAutoDownloadSettings::class, 'medium');
    }
}
