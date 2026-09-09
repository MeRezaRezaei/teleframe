<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPublicForwardsPublicForwardsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPublicForwardsPublicForwardsForwards;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStatsPublicForwardsPublicForwardsUsers;

/** Constructor model for stats.publicForwards of stats.PublicForwards (crc32 93037e20). */
final class TlStatsPublicForwardsPublicForwards extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stats_public_forwards_public_forwards';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function forwards(): HasMany
    {
        return $this->tlChild(TlStatsPublicForwardsPublicForwardsForwards::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlStatsPublicForwardsPublicForwardsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlStatsPublicForwardsPublicForwardsUsers::class);
    }
}
