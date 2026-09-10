<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeSettingsThemeSettings;

/** Anchor model for TL type BaseTheme (spec §4.1). */
final class TlBaseTheme extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_base_theme_base_theme_arctic';

    protected $guarded = [];

    public function baseTheme(): HasMany
    {
        return $this->hasMany(TlInputThemeSettingsInputThemeSettings::class, 'base_theme');
    }
    public function baseThemeThemeSettings(): HasMany
    {
        return $this->hasMany(TlThemeSettingsThemeSettings::class, 'base_theme');
    }
}
