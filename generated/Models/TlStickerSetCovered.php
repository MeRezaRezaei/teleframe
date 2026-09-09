<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlRecentMeUrlRecentMeUrlStickerSet;

/** Anchor model for TL type StickerSetCovered (spec §4.1). */
final class TlStickerSetCovered extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_sticker_set_covered';

    protected $guarded = [];

    public function set(): HasMany
    {
        return $this->hasMany(TlRecentMeUrlRecentMeUrlStickerSet::class, 'set');
    }
}
