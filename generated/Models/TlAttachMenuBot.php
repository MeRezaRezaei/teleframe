<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAttachMenuBotsBotAttachMenuBotsBot;

/** Anchor model for TL type AttachMenuBot (spec §4.1). */
final class TlAttachMenuBot extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_attach_menu_bot';

    protected $guarded = [];

    public function bot(): HasMany
    {
        return $this->hasMany(TlAttachMenuBotsBotAttachMenuBotsBot::class, 'bot');
    }
}
