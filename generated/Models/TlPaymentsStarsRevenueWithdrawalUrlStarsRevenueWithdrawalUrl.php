<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.starsRevenueWithdrawalUrl of payments.StarsRevenueWithdrawalUrl (crc32 1dab80b7). */
final class TlPaymentsStarsRevenueWithdrawalUrlStarsRevenueWithdrawalUrl extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_stars_revenue_withdrawal_url_star_0843bfeba80c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
    ];
}
