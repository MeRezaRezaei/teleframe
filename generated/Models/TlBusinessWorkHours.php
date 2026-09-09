<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUserFullUserFull;

/** Anchor model for TL type BusinessWorkHours (spec §4.1). */
final class TlBusinessWorkHours extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_business_work_hours';

    protected $guarded = [];

    public function businessWorkHours(): HasMany
    {
        return $this->hasMany(TlUserFullUserFull::class, 'business_work_hours');
    }
}
