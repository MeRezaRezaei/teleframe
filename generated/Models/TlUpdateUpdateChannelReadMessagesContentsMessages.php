<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param messages (table tl_update_update_channel_read_messages_contents__messages). */
final class TlUpdateUpdateChannelReadMessagesContentsMessages extends TlAnchorModel
{
    protected $table = 'tl_update_update_channel_read_messages_contents__messages';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
