<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param messages (table tl_messages_quick_replies_quick_replies__messages). */
final class TlMessagesQuickRepliesQuickRepliesMessages extends TlAnchorModel
{
    protected $table = 'tl_messages_quick_replies_quick_replies__messages';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
