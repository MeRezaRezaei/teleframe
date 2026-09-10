<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStickerSet;

/** Constructor model for stickerSetNoCovered of StickerSetCovered (crc32 77b15d1c). */
final class TlStickerSetCoveredStickerSetNoCovered extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_sticker_set_covered_sticker_set_no_covered';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function set(): BelongsTo
    {
        return $this->belongsTo(TlStickerSet::class, 'set');
    }
}
