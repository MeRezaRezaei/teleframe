<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotBusinessConnectionBotBusinessConnection;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConnectedBotConnectedBot;

/** Anchor model for TL type BusinessBotRights (spec §4.1). */
final class TlBusinessBotRights extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_bot_rights_business_bot_rights';

    protected $guarded = [];

    public function rights(): HasMany
    {
        return $this->hasMany(TlConnectedBotConnectedBot::class, 'rights');
    }
    public function rightsBotBusinessConnection(): HasMany
    {
        return $this->hasMany(TlBotBusinessConnectionBotBusinessConnection::class, 'rights');
    }
}
