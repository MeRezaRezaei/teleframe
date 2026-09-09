<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param users (table tl_stories_story_views_list_story_views_list__users). */
final class TlStoriesStoryViewsListStoryViewsListUsers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_story_views_list_story_views_list__users';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
