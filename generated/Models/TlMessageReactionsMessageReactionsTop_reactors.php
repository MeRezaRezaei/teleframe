<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param top_reactors (table tl_message_reactions_message_reactions__top_reactors). */
final class TlMessageReactionsMessageReactionsTop_reactors extends TlAnchorModel
{
    protected $table = 'tl_message_reactions_message_reactions__top_reactors';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
