<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param recent_reactions (table tl_message_reactions_message_reactions__recent_reactions). */
final class TlMessageReactionsMessageReactionsRecent_reactions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_reactions_message_reactions__recent_reactions';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
