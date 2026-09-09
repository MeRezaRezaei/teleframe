<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaGame;

/** Anchor model for TL type InputGame (spec §4.1). */
final class TlInputGame extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_game';

    protected $guarded = [];

    public function id(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaGame::class, 'tl_id');
    }
}
