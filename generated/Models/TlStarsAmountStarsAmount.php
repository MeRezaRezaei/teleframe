<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsAmount of StarsAmount (crc32 bbb6b4a3). */
final class TlStarsAmountStarsAmount extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_amount_stars_amount';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'amount' => 'int',
        'nanos' => 'int',
    ];
}
