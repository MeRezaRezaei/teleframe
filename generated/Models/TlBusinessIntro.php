<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type BusinessIntro (spec §4.1). */
final class TlBusinessIntro extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_intro_business_intro';

    protected $guarded = [];

    public function businessIntro(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'business_intro');
    }
}
