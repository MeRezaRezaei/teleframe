<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param ranges (table tl_channel_messages_filter_channel_messages_filter__ranges). */
final class TlChannelMessagesFilterChannelMessagesFilterRanges extends TlAnchorModel
{
    protected $table = 'tl_channel_messages_filter_channel_messages_filter__ranges';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
