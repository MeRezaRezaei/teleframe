<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param scores (table tl_messages_high_scores_high_scores__scores). */
final class TlMessagesHighScoresHighScoresScores extends TlAnchorModel
{
    protected $table = 'tl_messages_high_scores_high_scores__scores';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
