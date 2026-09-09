<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param reactions (table tl_stories_story_reactions_list_story_reactio_d7e48e0a40c9). */
final class TlStoriesStoryReactionsListStoryReactionsListReactions extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_story_reactions_list_story_reactio_d7e48e0a40c9';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
