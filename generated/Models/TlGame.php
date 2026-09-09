<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaGame;

/** Anchor model for TL type Game (spec §4.1). */
final class TlGame extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_game';

    protected $guarded = [];

    public function game(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaGame::class, 'game');
    }
}
