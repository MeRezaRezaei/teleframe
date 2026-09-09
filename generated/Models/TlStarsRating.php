<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type StarsRating (spec §4.1). */
final class TlStarsRating extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stars_rating';

    protected $guarded = [];

    public function starsMyPendingRating(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'stars_my_pending_rating');
    }
    public function starsRating(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'stars_rating');
    }
}
