<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSetCoveredStickerSetMultiCoveredCovers;

/** Constructor model for stickerSetMultiCovered of StickerSetCovered (crc32 3407e51b). */
final class TlStickerSetCoveredStickerSetMultiCovered extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_sticker_set_covered_sticker_set_multi_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'set' => 'string',
    ];

    public function covers(): HasMany
    {
        return $this->tlChild(TlStickerSetCoveredStickerSetMultiCoveredCovers::class);
    }
}
