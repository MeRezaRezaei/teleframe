<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for postInteractionCountersMessage of PostInteractionCounters (crc32 e7058e7f). */
final class TlPostInteractionCountersPostInteractionCountersMessage extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_post_interaction_counters_post_interaction_a4ecb5ab43c9';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'msg_id' => 'int',
        'views' => 'int',
        'forwards' => 'int',
        'reactions' => 'int',
    ];
}
