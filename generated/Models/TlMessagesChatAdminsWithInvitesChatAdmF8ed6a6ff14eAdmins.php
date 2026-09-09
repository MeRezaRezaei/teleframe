<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param admins (table tl_messages_chat_admins_with_invites_chat_adm_b1767129b10c). */
final class TlMessagesChatAdminsWithInvitesChatAdmF8ed6a6ff14eAdmins extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_admins_with_invites_chat_adm_b1767129b10c';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
