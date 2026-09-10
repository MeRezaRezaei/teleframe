<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInfoBotInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotMenuButton;

/** Anchor model for TL type BotMenuButton (spec §4.1). */
final class TlBotMenuButton extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_menu_button_bot_menu_button';

    protected $guarded = [];

    public function button(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotMenuButton::class, 'button');
    }
    public function menuButton(): HasMany
    {
        return $this->hasMany(TlBotInfoBotInfo::class, 'menu_button');
    }
}
