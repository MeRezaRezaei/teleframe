<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredDocuments;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredKeywords;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredPacks;

/** Constructor model for stickerSetFullCovered of StickerSetCovered (crc32 40d13c0e). */
final class TlStickerSetCoveredStickerSetFullCovered extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sticker_set_covered_sticker_set_full_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function packs(): HasMany
    {
        return $this->tlChild(TlStickerSetCoveredStickerSetFullCoveredPacks::class);
    }
    public function keywords(): HasMany
    {
        return $this->tlChild(TlStickerSetCoveredStickerSetFullCoveredKeywords::class);
    }
    public function documents(): HasMany
    {
        return $this->tlChild(TlStickerSetCoveredStickerSetFullCoveredDocuments::class);
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(TlStickerSet::class, 'set');
    }
}
