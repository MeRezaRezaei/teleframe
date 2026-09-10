<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChatFullChannelFull;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSetStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetCovered;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCovered;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetMultiCovered;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetNoCovered;

/** Anchor model for TL type StickerSet (spec §4.1). */
final class TlStickerSet extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_sticker_set_sticker_set';

    protected $guarded = [];

    public function emojiset(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'emojiset');
    }
    public function set(): HasMany
    {
        return $this->hasMany(TlMessagesStickerSetStickerSet::class, 'set');
    }
    public function setStickerSetCovered(): HasMany
    {
        return $this->hasMany(TlStickerSetCoveredStickerSetCovered::class, 'set');
    }
    public function setStickerSetFullCovered(): HasMany
    {
        return $this->hasMany(TlStickerSetCoveredStickerSetFullCovered::class, 'set');
    }
    public function setStickerSetMultiCovered(): HasMany
    {
        return $this->hasMany(TlStickerSetCoveredStickerSetMultiCovered::class, 'set');
    }
    public function setStickerSetNoCovered(): HasMany
    {
        return $this->hasMany(TlStickerSetCoveredStickerSetNoCovered::class, 'set');
    }
    public function stickerset(): HasMany
    {
        return $this->hasMany(TlChatFullChannelFull::class, 'stickerset');
    }
}
