<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlFoundStoryFoundStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPublicForwardPublicForwardStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReactionPublicRepost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewStoryViewPublicRepost;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebPageAttributeWebPageAttributeStory;

/** Anchor model for TL type StoryItem (spec §4.1). */
final class TlStoryItem extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_story_item_story_item';

    protected $guarded = [];

    public function story(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaStory::class, 'story');
    }
    public function storyFoundStory(): HasMany
    {
        return $this->hasMany(TlFoundStoryFoundStory::class, 'story');
    }
    public function storyPublicForwardStory(): HasMany
    {
        return $this->hasMany(TlPublicForwardPublicForwardStory::class, 'story');
    }
    public function storyStoryReactionPublicRepost(): HasMany
    {
        return $this->hasMany(TlStoryReactionStoryReactionPublicRepost::class, 'story');
    }
    public function storyStoryViewPublicRepost(): HasMany
    {
        return $this->hasMany(TlStoryViewStoryViewPublicRepost::class, 'story');
    }
    public function storyUpdateStory(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStory::class, 'story');
    }
    public function storyWebPageAttributeStory(): HasMany
    {
        return $this->hasMany(TlWebPageAttributeWebPageAttributeStory::class, 'story');
    }
}
