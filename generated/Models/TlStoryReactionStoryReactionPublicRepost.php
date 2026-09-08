<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for storyReactionPublicRepost of StoryReaction (crc32 cfcd0f13). */
final class TlStoryReactionStoryReactionPublicRepost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_story_reaction_story_reaction_public_repost';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer_id' => 'string',
        'story' => 'string',
    ];
}
