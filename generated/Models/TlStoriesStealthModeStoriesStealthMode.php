<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storiesStealthMode of StoriesStealthMode (crc32 712e27fd). */
final class TlStoriesStealthModeStoriesStealthMode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stories_stealth_mode_stories_stealth_mode';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'active_until_date' => 'int',
        'cooldown_until_date' => 'int',
    ];
}
