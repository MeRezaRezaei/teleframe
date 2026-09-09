<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_messages_exported_chat_invite_exported_cha_4638a2b5d812). */
final class TlMessagesExportedChatInviteExportedChaD180f3d61600Users extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_exported_chat_invite_exported_cha_4638a2b5d812';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
