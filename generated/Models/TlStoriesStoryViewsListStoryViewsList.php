<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsListStoryViewsListChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsListStoryViewsListUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryViewsListStoryViewsListViews;

/** Constructor model for stories.storyViewsList of stories.StoryViewsList (crc32 59d78fc5). */
final class TlStoriesStoryViewsListStoryViewsList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stories_story_views_list_story_views_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'views_count' => 'int',
        'forwards_count' => 'int',
        'reactions_count' => 'int',
        'next_offset' => 'string',
    ];

    public function views(): HasMany
    {
        return $this->tlChild(TlStoriesStoryViewsListStoryViewsListViews::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesStoryViewsListStoryViewsListChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesStoryViewsListStoryViewsListUsers::class);
    }
}
