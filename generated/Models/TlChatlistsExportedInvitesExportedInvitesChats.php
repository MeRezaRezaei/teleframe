<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_chatlists_exported_invites_exported_invites__chats). */
final class TlChatlistsExportedInvitesExportedInvitesChats extends TlAnchorModel
{
    protected $table = 'tl_chatlists_exported_invites_exported_invites__chats';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
