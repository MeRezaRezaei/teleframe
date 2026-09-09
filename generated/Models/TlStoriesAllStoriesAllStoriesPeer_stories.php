<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param peer_stories (table tl_stories_all_stories_all_stories__peer_stories). */
final class TlStoriesAllStoriesAllStoriesPeer_stories extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_all_stories_all_stories__peer_stories';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
