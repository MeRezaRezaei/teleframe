<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type messages.ChatFull (spec §4.1). */
final class TlMessagesChatFull extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_full_chat_full';

    protected $guarded = [];
}
