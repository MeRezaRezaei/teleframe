<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewsStoryViewsReactions;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewsStoryViewsRecent_viewers;

/** Constructor model for storyViews of StoryViews (crc32 8d595cd6). */
final class TlStoryViewsStoryViews extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_story_views_story_views';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'has_viewers' => 'bool',
        'views_count' => 'int',
        'forwards_count' => 'int',
        'reactions_count' => 'int',
    ];

    public function reactions(): HasMany
    {
        return $this->tlChild(TlStoryViewsStoryViewsReactions::class);
    }
    public function recentViewers(): HasMany
    {
        return $this->tlChild(TlStoryViewsStoryViewsRecent_viewers::class);
    }
}
