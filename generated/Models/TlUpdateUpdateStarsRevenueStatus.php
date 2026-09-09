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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsRevenueStatus;

/** Constructor model for updateStarsRevenueStatus of Update (crc32 a584b019). */
final class TlUpdateUpdateStarsRevenueStatus extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_update_update_stars_revenue_status';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(TlStarsRevenueStatus::class, 'status');
    }
}
