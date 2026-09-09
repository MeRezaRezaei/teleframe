<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type StarRefProgram (spec §4.1). */
final class TlStarRefProgram extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_ref_program';

    protected $guarded = [];

    public function starrefProgram(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'starref_program');
    }
}
