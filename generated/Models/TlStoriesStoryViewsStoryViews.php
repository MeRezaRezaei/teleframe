<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsStoryViewsViews;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsStoryViewsUsers;

/** Constructor model for stories.storyViews of stories.StoryViews (crc32 de9eed1d). */
final class TlStoriesStoryViewsStoryViews extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stories_story_views_story_views';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function views(): HasMany
    {
        return $this->tlChild(TlStoriesStoryViewsStoryViewsViews::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesStoryViewsStoryViewsUsers::class);
    }
}
