<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAllStoriesAllStories;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoriesAllStoriesAllStoriesNotModified;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateStoriesStealthMode;

/** Anchor model for TL type StoriesStealthMode (spec §4.1). */
final class TlStoriesStealthMode extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_stealth_mode_stories_stealth_mode';

    protected $guarded = [];

    public function stealthMode(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateStoriesStealthMode::class, 'stealth_mode');
    }
    public function stealthModeStoriesAllStories(): HasMany
    {
        return $this->hasMany(TlStoriesAllStoriesAllStories::class, 'stealth_mode');
    }
    public function stealthModeStoriesAllStoriesNotModified(): HasMany
    {
        return $this->hasMany(TlStoriesAllStoriesAllStoriesNotModified::class, 'stealth_mode');
    }
}
