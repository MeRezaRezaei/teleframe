<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem;

/** Anchor model for TL type StoryFwdHeader (spec §4.1). */
final class TlStoryFwdHeader extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_story_fwd_header_story_fwd_header';

    protected $guarded = [];

    public function fwdFrom(): HasMany
    {
        return $this->hasMany(TlStoryItemStoryItem::class, 'fwd_from');
    }
}
