<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type ChatInviteImporter (spec §4.1). */
final class TlChatInviteImporter extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_chat_invite_importer_chat_invite_importer';

    protected $guarded = [];
}
