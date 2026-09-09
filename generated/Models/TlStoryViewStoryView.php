<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlReaction;

/** Constructor model for storyView of StoryView (crc32 b0bdeac5). */
final class TlStoryViewStoryView extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_story_view_story_view';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'blocked' => 'bool',
        'blocked_my_stories_from' => 'bool',
        'user_id' => 'int',
        'date' => 'int',
    ];

    public function reaction(): BelongsTo
    {
        return $this->belongsTo(TlReaction::class, 'reaction');
    }
}
