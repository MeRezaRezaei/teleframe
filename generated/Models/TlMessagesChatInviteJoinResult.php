<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.ChatInviteJoinResult (spec §4.1). */
final class TlMessagesChatInviteJoinResult extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_join_result_chat_invi_71ed5b26df07';

    protected $guarded = [];
}
