<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.ChatAdminsWithInvites (spec §4.1). */
final class TlMessagesChatAdminsWithInvites extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_admins_with_invites';

    protected $guarded = [];
}
