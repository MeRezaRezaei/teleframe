<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param quick_replies (table tl_messages_quick_replies_quick_replies__quick_replies). */
final class TlMessagesQuickRepliesQuickRepliesQuick_replies extends TlAnchorModel
{
    protected $table = 'tl_messages_quick_replies_quick_replies__quick_replies';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
