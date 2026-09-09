<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b). */
final class TlMessagesChatInviteImportersChatInviteImportersUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_importers_chat_invite_23f2c7da2e5b';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
