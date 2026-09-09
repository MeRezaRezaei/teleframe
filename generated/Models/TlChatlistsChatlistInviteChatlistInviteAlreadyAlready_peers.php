<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param already_peers (table tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539). */
final class TlChatlistsChatlistInviteChatlistInviteAlreadyAlready_peers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chatlists_chatlist_invite_chatlist_invite__b7f3a8202539';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
