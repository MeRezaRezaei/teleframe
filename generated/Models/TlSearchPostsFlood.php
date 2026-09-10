<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesMessagesMessagesSlice;

/** Anchor model for TL type SearchPostsFlood (spec §4.1). */
final class TlSearchPostsFlood extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_search_posts_flood_search_posts_flood';

    protected $guarded = [];

    public function searchFlood(): HasMany
    {
        return $this->hasMany(TlMessagesMessagesMessagesSlice::class, 'search_flood');
    }
}
