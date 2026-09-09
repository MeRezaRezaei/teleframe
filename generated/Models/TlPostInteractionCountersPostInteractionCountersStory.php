<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for postInteractionCountersStory of PostInteractionCounters (crc32 8a480e27). */
final class TlPostInteractionCountersPostInteractionCountersStory extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_post_interaction_counters_post_interaction_b4f5e2e1599f';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'story_id' => 'int',
        'views' => 'int',
        'forwards' => 'int',
        'reactions' => 'int',
    ];
}
