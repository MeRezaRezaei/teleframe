<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param videos (table tl_help_premium_promo_premium_promo__videos). */
final class TlHelpPremiumPromoPremiumPromoVideos extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_premium_promo_premium_promo__videos';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
