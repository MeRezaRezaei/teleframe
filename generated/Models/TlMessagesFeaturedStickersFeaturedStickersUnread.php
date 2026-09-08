<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param unread (table tl_messages_featured_stickers_featured_stickers__unread). */
final class TlMessagesFeaturedStickersFeaturedStickersUnread extends TlAnchorModel
{
    protected $table = 'tl_messages_featured_stickers_featured_stickers__unread';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
