<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param pinned_to_top (table tl_stories_stories_stories__pinned_to_top). */
final class TlStoriesStoriesStoriesPinned_to_top extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_stories_stories__pinned_to_top';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
