<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesUpdateShort;

/** Anchor model for TL type Update (spec §4.1). */
final class TlUpdate extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_update';

    protected $guarded = [];

    public function updateUpdateShort(): HasMany
    {
        return $this->hasMany(TlUpdatesUpdateShort::class, 'update');
    }
}
