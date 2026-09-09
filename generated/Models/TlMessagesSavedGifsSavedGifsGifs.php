<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param gifs (table tl_messages_saved_gifs_saved_gifs__gifs). */
final class TlMessagesSavedGifsSavedGifsGifs extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_saved_gifs_saved_gifs__gifs';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
