<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for statsGroupTopInviter of StatsGroupTopInviter (crc32 535f779d). */
final class TlStatsGroupTopInviterStatsGroupTopInviter extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stats_group_top_inviter_stats_group_top_inviter';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'int',
        'invitations' => 'int',
    ];
}
