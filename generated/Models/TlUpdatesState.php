<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesPeerDialogsPeerDialogs;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifference;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdatesDifferenceDifferenceSlice;

/** Anchor model for TL type updates.State (spec §4.1). */
final class TlUpdatesState extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_updates_state_state';

    protected $guarded = [];

    public function intermediateState(): HasMany
    {
        return $this->hasMany(TlUpdatesDifferenceDifferenceSlice::class, 'intermediate_state');
    }
    public function state(): HasMany
    {
        return $this->hasMany(TlUpdatesDifferenceDifference::class, 'state');
    }
    public function stateMessagesPeerDialogs(): HasMany
    {
        return $this->hasMany(TlMessagesPeerDialogsPeerDialogs::class, 'state');
    }
}
