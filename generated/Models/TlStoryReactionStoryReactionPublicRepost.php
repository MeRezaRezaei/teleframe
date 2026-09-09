<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItem;

/** Constructor model for storyReactionPublicRepost of StoryReaction (crc32 cfcd0f13). */
final class TlStoryReactionStoryReactionPublicRepost extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_story_reaction_story_reaction_public_repost';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function story(): BelongsTo
    {
        return $this->belongsTo(TlStoryItem::class, 'story');
    }
}
