<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeTheme;

/** Anchor model for TL type ThemeSettings (spec §4.1). */
final class TlThemeSettings extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_theme_settings_theme_settings';

    protected $guarded = [];

    public function settings(): HasMany
    {
        return $this->hasMany(TlWebPageAttributeWebPageAttributeTheme::class, 'settings');
    }
}
