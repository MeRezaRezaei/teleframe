<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param views (table tl_stories_story_views_list_story_views_list__views). */
final class TlStoriesStoryViewsListStoryViewsListViews extends TlAnchorModel
{
    protected $table = 'tl_stories_story_views_list_story_views_list__views';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
