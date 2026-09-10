<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionSetChatWallPaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlThemeSettingsThemeSettings;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePeerWallpaper;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type WallPaper (spec §4.1). */
final class TlWallPaper extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_wall_paper_wall_paper';

    protected $guarded = [];

    public function newValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper::class, 'new_value');
    }
    public function prevValue(): HasMany
    {
        return $this->hasMany(TlChannelAdminLogEventActionChannelAdminLogEventActionChangeWallpaper::class, 'prev_value');
    }
    public function wallpaper(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'wallpaper');
    }
    public function wallpaperMessageActionSetChatWallPaper(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionSetChatWallPaper::class, 'wallpaper');
    }
    public function wallpaperThemeSettings(): HasMany
    {
        return $this->hasMany(TlThemeSettingsThemeSettings::class, 'wallpaper');
    }
    public function wallpaperUpdatePeerWallpaper(): HasMany
    {
        return $this->hasMany(TlUpdateUpdatePeerWallpaper::class, 'wallpaper');
    }
    public function wallpaperUserFull(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'wallpaper');
    }
}
