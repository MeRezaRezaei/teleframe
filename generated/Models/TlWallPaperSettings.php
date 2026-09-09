<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperWallPaperNoFile;

/** Anchor model for TL type WallPaperSettings (spec §4.1). */
final class TlWallPaperSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_wall_paper_settings';

    protected $guarded = [];

    public function settings(): HasMany
    {
        return $this->hasMany(TlWallPaperWallPaper::class, 'settings');
    }
    public function settingsWallPaperNoFile(): HasMany
    {
        return $this->hasMany(TlWallPaperWallPaperNoFile::class, 'settings');
    }
    public function wallpaperSettings(): HasMany
    {
        return $this->hasMany(TlInputThemeSettingsInputThemeSettings::class, 'wallpaper_settings');
    }
}
