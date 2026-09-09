<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotChatBoost;

/** Anchor model for TL type Boost (spec §4.1). */
final class TlBoost extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_boost';

    protected $guarded = [];

    public function boost(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotChatBoost::class, 'boost');
    }
}
