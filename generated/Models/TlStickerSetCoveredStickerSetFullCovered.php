<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredPacks;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredKeywords;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetFullCoveredDocuments;

/** Constructor model for stickerSetFullCovered of StickerSetCovered (crc32 40d13c0e). */
final class TlStickerSetCoveredStickerSetFullCovered extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_sticker_set_covered_sticker_set_full_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'set' => 'string',
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
}
