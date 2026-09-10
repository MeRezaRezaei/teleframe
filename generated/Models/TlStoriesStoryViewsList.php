<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type stories.StoryViewsList (spec §4.1). */
final class TlStoriesStoryViewsList extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_story_views_list_story_views_list';

    protected $guarded = [];
}
