<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPromoDataPromoData;

/** Anchor model for TL type PendingSuggestion (spec §4.1). */
final class TlPendingSuggestion extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_pending_suggestion_pending_suggestion';

    protected $guarded = [];

    public function customPendingSuggestion(): HasMany
    {
        return $this->hasMany(TlHelpPromoDataPromoData::class, 'custom_pending_suggestion');
    }
}
