<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeBackdrop;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributeModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftAttributeStarGiftAttributePattern;

/** Anchor model for TL type StarGiftAttributeRarity (spec §4.1). */
final class TlStarGiftAttributeRarity extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_attribute_rarity_star_gift_attribute_rarity';

    protected $guarded = [];

    public function rarity(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributeModel::class, 'rarity');
    }
    public function rarityStarGiftAttributeBackdrop(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributeBackdrop::class, 'rarity');
    }
    public function rarityStarGiftAttributePattern(): HasMany
    {
        return $this->hasMany(TlStarGiftAttributeStarGiftAttributePattern::class, 'rarity');
    }
}
