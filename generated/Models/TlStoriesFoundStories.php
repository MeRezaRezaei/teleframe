<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type stories.FoundStories (spec §4.1). */
final class TlStoriesFoundStories extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stories_found_stories_found_stories';

    protected $guarded = [];
}
