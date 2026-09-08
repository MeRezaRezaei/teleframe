<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storyReaction of StoryReaction (crc32 6090d6d5). */
final class TlStoryReactionStoryReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_story_reaction_story_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer_id' => 'string',
        'date' => 'int',
        'reaction' => 'string',
    ];
}
