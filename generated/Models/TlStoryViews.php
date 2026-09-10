<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem;

/** Anchor model for TL type StoryViews (spec §4.1). */
final class TlStoryViews extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_story_views_story_views';

    protected $guarded = [];

    public function views(): HasMany
    {
        return $this->hasMany(TlStoryItemStoryItem::class, 'views');
    }
}
