<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettings;

/** Anchor model for TL type InputWallPaper (spec §4.1). */
final class TlInputWallPaper extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_wall_paper';

    protected $guarded = [];

    public function wallpaper(): HasMany
    {
        return $this->hasMany(TlInputThemeSettingsInputThemeSettings::class, 'wallpaper');
    }
}
