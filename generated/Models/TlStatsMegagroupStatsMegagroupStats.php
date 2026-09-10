<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsAbsValueAndPrev;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsDateRangeDays;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsGraph;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStatsTop_admins;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStatsTop_inviters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStatsTop_posters;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStatsUsers;

/** Constructor model for stats.megagroupStats of stats.MegagroupStats (crc32 ef7ff916). */
final class TlStatsMegagroupStatsMegagroupStats extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stats_megagroup_stats_megagroup_stats';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function topPosters(): HasMany
    {
        return $this->tlChild(TlStatsMegagroupStatsMegagroupStatsTop_posters::class);
    }
    public function topAdmins(): HasMany
    {
        return $this->tlChild(TlStatsMegagroupStatsMegagroupStatsTop_admins::class);
    }
    public function topInviters(): HasMany
    {
        return $this->tlChild(TlStatsMegagroupStatsMegagroupStatsTop_inviters::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStatsMegagroupStatsMegagroupStatsUsers::class);
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TlStatsDateRangeDays::class, 'period');
    }
    public function members(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'members');
    }
    public function messages(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'messages');
    }
    public function viewers(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'viewers');
    }
    public function posters(): BelongsTo
    {
        return $this->belongsTo(TlStatsAbsValueAndPrev::class, 'posters');
    }
    public function growthGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'growth_graph');
    }
    public function membersGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'members_graph');
    }
    public function newMembersBySourceGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'new_members_by_source_graph');
    }
    public function languagesGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'languages_graph');
    }
    public function messagesGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'messages_graph');
    }
    public function actionsGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'actions_graph');
    }
    public function topHoursGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'top_hours_graph');
    }
    public function weekdaysGraph(): BelongsTo
    {
        return $this->belongsTo(TlStatsGraph::class, 'weekdays_graph');
    }
}
