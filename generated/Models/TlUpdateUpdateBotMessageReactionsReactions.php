<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param reactions (table tl_update_update_bot_message_reactions__reactions). */
final class TlUpdateUpdateBotMessageReactionsReactions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_update_update_bot_message_reactions__reactions';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
