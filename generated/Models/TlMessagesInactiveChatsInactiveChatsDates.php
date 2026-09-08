<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param dates (table tl_messages_inactive_chats_inactive_chats__dates). */
final class TlMessagesInactiveChatsInactiveChatsDates extends TlAnchorModel
{
    protected $table = 'tl_messages_inactive_chats_inactive_chats__dates';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
