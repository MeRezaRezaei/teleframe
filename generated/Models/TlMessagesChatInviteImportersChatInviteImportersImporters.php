<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param importers (table tl_messages_chat_invite_importers_chat_invite_8f980112eace). */
final class TlMessagesChatInviteImportersChatInviteImportersImporters extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_messages_chat_invite_importers_chat_invite_8f980112eace';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
