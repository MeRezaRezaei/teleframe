<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param message_colors (table tl_theme_settings_theme_settings__message_colors). */
final class TlThemeSettingsThemeSettingsMessage_colors extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_theme_settings_theme_settings__message_colors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
