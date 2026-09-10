<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type payments.StarGifts (spec §4.1). */
final class TlPaymentsStarGifts extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payments_star_gifts_star_gifts';

    protected $guarded = [];
}
