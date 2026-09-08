<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param recent_voters (table tl_poll_answer_voters_poll_answer_voters__recent_voters). */
final class TlPollAnswerVotersPollAnswerVotersRecent_voters extends TlAnchorModel
{
    protected $table = 'tl_poll_answer_voters_poll_answer_voters__recent_voters';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
