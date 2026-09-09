<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBaseTheme;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputThemeSettingsInputThemeSettingsMessage_colors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputWallPaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWallPaperSettings;

/** Constructor model for inputThemeSettings of InputThemeSettings (crc32 8fde504f). */
final class TlInputThemeSettingsInputThemeSettings extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_theme_settings_input_theme_settings';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'message_colors_animated' => 'bool',
        'accent_color' => 'int',
        'outbox_accent_color' => 'int',
    ];

    public function messageColors(): HasMany
    {
        return $this->tlChild(TlInputThemeSettingsInputThemeSettingsMessage_colors::class);
    }

    public function baseTheme(): BelongsTo
    {
        return $this->belongsTo(TlBaseTheme::class, 'base_theme');
    }
    public function wallpaper(): BelongsTo
    {
        return $this->belongsTo(TlInputWallPaper::class, 'wallpaper');
    }
    public function wallpaperSettings(): BelongsTo
    {
        return $this->belongsTo(TlWallPaperSettings::class, 'wallpaper_settings');
    }
}
