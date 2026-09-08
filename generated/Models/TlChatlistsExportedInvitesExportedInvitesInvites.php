<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param invites (table tl_chatlists_exported_invites_exported_invites__invites). */
final class TlChatlistsExportedInvitesExportedInvitesInvites extends TlAnchorModel
{
    protected $table = 'tl_chatlists_exported_invites_exported_invites__invites';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
