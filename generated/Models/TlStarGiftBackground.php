<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGift;

/** Anchor model for TL type StarGiftBackground (spec §4.1). */
final class TlStarGiftBackground extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_star_gift_background_star_gift_background';

    protected $guarded = [];

    public function background(): HasMany
    {
        return $this->hasMany(TlStarGiftStarGift::class, 'background');
    }
}
