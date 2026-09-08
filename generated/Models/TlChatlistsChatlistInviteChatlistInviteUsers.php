<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_chatlists_chatlist_invite_chatlist_invite__users). */
final class TlChatlistsChatlistInviteChatlistInviteUsers extends TlAnchorModel
{
    protected $table = 'tl_chatlists_chatlist_invite_chatlist_invite__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
