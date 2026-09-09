<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotBusinessConnect;

/** Anchor model for TL type BotBusinessConnection (spec §4.1). */
final class TlBotBusinessConnection extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_bot_business_connection';

    protected $guarded = [];

    public function connection(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotBusinessConnect::class, 'connection');
    }
}
