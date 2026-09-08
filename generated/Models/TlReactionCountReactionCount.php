<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for reactionCount of ReactionCount (crc32 a3d1cb80). */
final class TlReactionCountReactionCount extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_reaction_count_reaction_count';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'chosen_order' => 'int',
        'reaction' => 'string',
        'count' => 'int',
    ];
}
