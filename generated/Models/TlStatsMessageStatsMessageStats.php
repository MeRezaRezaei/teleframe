<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraph;

/** Constructor model for stats.messageStats of stats.MessageStats (crc32 7fe91c14). */
final class TlStatsMessageStatsMessageStats extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stats_message_stats_message_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function viewsGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'views_graph');
    }
    public function reactionsByEmotionGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'reactions_by_emotion_graph');
    }
}
