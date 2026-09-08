<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param old_reactions (table tl_update_update_bot_message_reaction__old_reactions). */
final class TlUpdateUpdateBotMessageReactionOld_reactions extends TlAnchorModel
{
    protected $table = 'tl_update_update_bot_message_reaction__old_reactions';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
