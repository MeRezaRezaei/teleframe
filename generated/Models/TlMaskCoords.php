<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocumentAttributeDocumentAttributeSticker;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStickerSetItemInputStickerSetItem;

/** Anchor model for TL type MaskCoords (spec §4.1). */
final class TlMaskCoords extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_mask_coords_mask_coords';

    protected $guarded = [];

    public function maskCoords(): HasMany
    {
        return $this->hasMany(TlDocumentAttributeDocumentAttributeSticker::class, 'mask_coords');
    }
    public function maskCoordsInputStickerSetItem(): HasMany
    {
        return $this->hasMany(TlInputStickerSetItemInputStickerSetItem::class, 'mask_coords');
    }
}
