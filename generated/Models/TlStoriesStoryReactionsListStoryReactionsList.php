<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryReactionsListStoryReactionsListChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryReactionsListStoryReactionsListReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoryReactionsListStoryReactionsListUsers;

/** Constructor model for stories.storyReactionsList of stories.StoryReactionsList (crc32 aa5f789c). */
final class TlStoriesStoryReactionsListStoryReactionsList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stories_story_reactions_list_story_reactions_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function reactions(): HasMany
    {
        return $this->tlChild(TlStoriesStoryReactionsListStoryReactionsListReactions::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesStoryReactionsListStoryReactionsListChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesStoryReactionsListStoryReactionsListUsers::class);
    }
}
