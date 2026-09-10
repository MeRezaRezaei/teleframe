<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type ChatAdminWithInvites (spec §4.1). */
final class TlChatAdminWithInvites extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_admin_with_invites_chat_admin_with_invites';

    protected $guarded = [];
}
