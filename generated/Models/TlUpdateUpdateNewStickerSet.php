<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesStickerSet;

/** Constructor model for updateNewStickerSet of Update (crc32 688a30aa). */
final class TlUpdateUpdateNewStickerSet extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_new_sticker_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function stickerset(): BelongsTo
    {
        return $this->belongsTo(TlMessagesStickerSet::class, 'stickerset');
    }
}
