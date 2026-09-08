<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param votes (table tl_messages_votes_list_votes_list__votes). */
final class TlMessagesVotesListVotesListVotes extends TlAnchorModel
{
    protected $table = 'tl_messages_votes_list_votes_list__votes';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
