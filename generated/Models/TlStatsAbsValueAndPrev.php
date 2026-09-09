<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsBroadcastStatsBroadcastStats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsMegagroupStatsMegagroupStats;

/** Anchor model for TL type StatsAbsValueAndPrev (spec §4.1). */
final class TlStatsAbsValueAndPrev extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stats_abs_value_and_prev';

    protected $guarded = [];

    public function followers(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'followers');
    }
    public function members(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'members');
    }
    public function messages(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'messages');
    }
    public function posters(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'posters');
    }
    public function reactionsPerPost(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'reactions_per_post');
    }
    public function reactionsPerStory(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'reactions_per_story');
    }
    public function sharesPerPost(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'shares_per_post');
    }
    public function sharesPerStory(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'shares_per_story');
    }
    public function viewers(): HasMany
    {
        return $this->hasMany(TlStatsMegagroupStatsMegagroupStats::class, 'viewers');
    }
    public function viewsPerPost(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'views_per_post');
    }
    public function viewsPerStory(): HasMany
    {
        return $this->hasMany(TlStatsBroadcastStatsBroadcastStats::class, 'views_per_story');
    }
}
