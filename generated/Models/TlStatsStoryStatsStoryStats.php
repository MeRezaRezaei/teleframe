<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for stats.storyStats of stats.StoryStats (crc32 50cd067c). */
final class TlStatsStoryStatsStoryStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stats_story_stats_story_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'views_graph' => 'string',
        'reactions_by_emotion_graph' => 'string',
    ];
}
