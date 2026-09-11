<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Domain model for stories (TL types: StoryItem, stories.Albums, stories.AllStories, stories.CanSendStoryCount, stories.FoundStories, stories.PeerStories, stories.Stories, stories.StoryReactionsList, stories.StoryViews, stories.StoryViewsList). */
final class TlStorie extends TlAnchorModel
{
    use AccountScoped, PeerResolution;

    protected $table = 'tf_stories';

    protected $guarded = [];
}
