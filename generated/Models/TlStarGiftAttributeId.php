<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeCounterStarGiftAttributeCounter;

/** Anchor model for TL type StarGiftAttributeId (spec §4.1). */
final class TlStarGiftAttributeId extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_id';

    protected $guarded = [];

    public function attribute(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeCounterStarGiftAttributeCounter::class, 'attribute');
    }
}
