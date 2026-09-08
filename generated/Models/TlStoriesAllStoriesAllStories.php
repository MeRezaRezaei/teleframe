<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAllStoriesAllStoriesPeer_stories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAllStoriesAllStoriesChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAllStoriesAllStoriesUsers;

/** Constructor model for stories.allStories of stories.AllStories (crc32 6efc5e81). */
final class TlStoriesAllStoriesAllStories extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stories_all_stories_all_stories';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_more' => 'bool',
        'count' => 'int',
        'state' => 'string',
        'stealth_mode' => 'string',
    ];

    public function peerStories(): HasMany
    {
        return $this->tlChild(TlStoriesAllStoriesAllStoriesPeer_stories::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStoriesAllStoriesAllStoriesChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStoriesAllStoriesAllStoriesUsers::class);
    }
}
