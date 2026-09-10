<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type stories.StoryReactionsList (spec §4.1). */
final class TlStoriesStoryReactionsList extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_story_reactions_list_story_reactions_list';

    protected $guarded = [];
}
