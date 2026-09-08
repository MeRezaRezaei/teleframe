<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param missing_invitees (table tl_messages_invited_users_invited_users__missing_invitees). */
final class TlMessagesInvitedUsersInvitedUsersMissing_invitees extends TlAnchorModel
{
    protected $table = 'tl_messages_invited_users_invited_users__missing_invitees';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
