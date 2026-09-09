<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesFoundStoriesFoundStoriesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesFoundStoriesFoundStoriesStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesFoundStoriesFoundStoriesUsers;

/** Constructor model for stories.foundStories of stories.FoundStories (crc32 e2de7737). */
final class TlStoriesFoundStoriesFoundStories extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stories_found_stories_found_stories';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function stories(): HasMany
    {
        return $this->tlChild(TlStoriesFoundStoriesFoundStoriesStories::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesFoundStoriesFoundStoriesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesFoundStoriesFoundStoriesUsers::class);
    }
}
