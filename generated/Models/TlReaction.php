<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlConfigConfig;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMediaAreaMediaAreaSuggestedReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagePeerReactionMessagePeerReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReactionCountReactionCount;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlSavedReactionTagSavedReactionTag;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryItemStoryItem;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryReactionStoryReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStoryViewStoryView;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateNewStoryReaction;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateSentStoryReaction;

/** Anchor model for TL type Reaction (spec §4.1). */
final class TlReaction extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_reaction_reaction_custom_emoji';

    protected $guarded = [];

    public function reaction(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateSentStoryReaction::class, 'reaction');
    }
    public function reactionMediaAreaSuggestedReaction(): HasMany
    {
        return $this->hasMany(TlMediaAreaMediaAreaSuggestedReaction::class, 'reaction');
    }
    public function reactionMessagePeerReaction(): HasMany
    {
        return $this->hasMany(TlMessagePeerReactionMessagePeerReaction::class, 'reaction');
    }
    public function reactionReactionCount(): HasMany
    {
        return $this->hasMany(TlReactionCountReactionCount::class, 'reaction');
    }
    public function reactionSavedReactionTag(): HasMany
    {
        return $this->hasMany(TlSavedReactionTagSavedReactionTag::class, 'reaction');
    }
    public function reactionStoryReaction(): HasMany
    {
        return $this->hasMany(TlStoryReactionStoryReaction::class, 'reaction');
    }
    public function reactionStoryView(): HasMany
    {
        return $this->hasMany(TlStoryViewStoryView::class, 'reaction');
    }
    public function reactionUpdateNewStoryReaction(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateNewStoryReaction::class, 'reaction');
    }
    public function reactionsDefault(): HasMany
    {
        return $this->hasMany(TlConfigConfig::class, 'reactions_default');
    }
    public function sentReaction(): HasMany
    {
        return $this->hasMany(TlStoryItemStoryItem::class, 'sent_reaction');
    }
}
