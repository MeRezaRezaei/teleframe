<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_phone_group_call_stars_group_call_stars__chats). */
final class TlPhoneGroupCallStarsGroupCallStarsChats extends TlAnchorModel
{
    protected $table = 'tl_phone_group_call_stars_group_call_stars__chats';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
