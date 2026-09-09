<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsAmount;

/** Constructor model for updateStarsBalance of Update (crc32 4e80a379). */
final class TlUpdateUpdateStarsBalance extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_stars_balance';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function balance(): BelongsTo
    {
        return $this->belongsTo(TlStarsAmount::class, 'balance');
    }
}
