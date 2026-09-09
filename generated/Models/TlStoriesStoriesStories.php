<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoriesStoriesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoriesStoriesPinned_to_top;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoriesStoriesStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesStoriesStoriesUsers;

/** Constructor model for stories.stories of stories.Stories (crc32 63c3dd0a). */
final class TlStoriesStoriesStories extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stories_stories_stories';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
    ];

    public function stories(): HasMany
    {
        return $this->tlChild(TlStoriesStoriesStoriesStories::class);
    }
    public function pinnedToTop(): HasMany
    {
        return $this->tlChild(TlStoriesStoriesStoriesPinned_to_top::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesStoriesStoriesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesStoriesStoriesUsers::class);
    }
}
