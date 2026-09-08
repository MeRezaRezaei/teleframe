<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param statuses (table tl_account_emoji_statuses_emoji_statuses__statuses). */
final class TlAccountEmojiStatusesEmojiStatusesStatuses extends TlAnchorModel
{
    protected $table = 'tl_account_emoji_statuses_emoji_statuses__statuses';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
