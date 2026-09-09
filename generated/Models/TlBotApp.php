<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionBotAllowed;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesBotAppBotApp;

/** Anchor model for TL type BotApp (spec §4.1). */
final class TlBotApp extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_app';

    protected $guarded = [];

    public function app(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionBotAllowed::class, 'app');
    }
    public function appMessagesBotApp(): HasMany
    {
        return $this->hasMany(TlMessagesBotAppBotApp::class, 'app');
    }
}
