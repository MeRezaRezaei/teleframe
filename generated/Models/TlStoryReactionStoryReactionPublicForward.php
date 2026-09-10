<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessage;

/** Constructor model for storyReactionPublicForward of StoryReaction (crc32 bbab2643). */
final class TlStoryReactionStoryReactionPublicForward extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_story_reaction_story_reaction_public_forward';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TlMessage::class, 'message');
    }
}
