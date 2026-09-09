<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param sets (table tl_messages_found_sticker_sets_found_sticker_sets__sets). */
final class TlMessagesFoundStickerSetsFoundStickerSetsSets extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_found_sticker_sets_found_sticker_sets__sets';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
