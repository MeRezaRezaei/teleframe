<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for starsRevenueStatus of StarsRevenueStatus (crc32 febe5491). */
final class TlStarsRevenueStatusStarsRevenueStatus extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stars_revenue_status_stars_revenue_status';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'withdrawal_enabled' => 'bool',
        'next_withdrawal_at' => 'int',
    ];

    public function currentBalance(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'current_balance');
    }
    public function availableBalance(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'available_balance');
    }
    public function overallRevenue(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'overall_revenue');
    }
}
