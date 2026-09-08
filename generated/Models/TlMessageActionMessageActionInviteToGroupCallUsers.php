<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_message_action_message_action_invite_to_gr_2ecee64f63f9). */
final class TlMessageActionMessageActionInviteToGroupCallUsers extends TlAnchorModel
{
    protected $table = 'tl_message_action_message_action_invite_to_gr_2ecee64f63f9';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
