<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewStickerSet;

/** Anchor model for TL type messages.StickerSet (spec §4.1). */
final class TlMessagesStickerSet extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_sticker_set_sticker_set';

    protected $guarded = [];

    public function stickerset(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewStickerSet::class, 'stickerset');
    }
}
