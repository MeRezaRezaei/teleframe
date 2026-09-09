<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSet;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetMultiCoveredCovers;

/** Constructor model for stickerSetMultiCovered of StickerSetCovered (crc32 3407e51b). */
final class TlStickerSetCoveredStickerSetMultiCovered extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sticker_set_covered_sticker_set_multi_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function covers(): HasMany
    {
        return $this->tlChild(TlStickerSetCoveredStickerSetMultiCoveredCovers::class);
    }

    public function set(): BelongsTo
    {
        return $this->belongsTo(TlStickerSet::class, 'set');
    }
}
