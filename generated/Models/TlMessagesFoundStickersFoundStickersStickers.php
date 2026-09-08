<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param stickers (table tl_messages_found_stickers_found_stickers__stickers). */
final class TlMessagesFoundStickersFoundStickersStickers extends TlAnchorModel
{
    protected $table = 'tl_messages_found_stickers_found_stickers__stickers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
