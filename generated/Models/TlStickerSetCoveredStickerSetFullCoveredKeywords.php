<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param keywords (table tl_sticker_set_covered_sticker_set_full_covered__keywords). */
final class TlStickerSetCoveredStickerSetFullCoveredKeywords extends TlAnchorModel
{
    protected $table = 'tl_sticker_set_covered_sticker_set_full_covered__keywords';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
