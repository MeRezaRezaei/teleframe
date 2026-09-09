<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_chat_invite_join_result_chat_invi_8cf578081a5f). */
final class TlMessagesChatInviteJoinResultChatInviDacd8245b982Users extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_join_result_chat_invi_8cf578081a5f';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
