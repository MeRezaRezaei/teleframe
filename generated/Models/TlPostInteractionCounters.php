<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type PostInteractionCounters (spec §4.1). */
final class TlPostInteractionCounters extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_post_interaction_counters_post_interaction_a4ecb5ab43c9';

    protected $guarded = [];
}
