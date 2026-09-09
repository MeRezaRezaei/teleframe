<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_chat_admins_with_invites_chat_adm_3175fc4f3da2). */
final class TlMessagesChatAdminsWithInvitesChatAdmF8ed6a6ff14eUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_admins_with_invites_chat_adm_3175fc4f3da2';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
