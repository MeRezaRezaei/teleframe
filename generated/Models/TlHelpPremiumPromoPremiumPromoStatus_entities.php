<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param status_entities (table tl_help_premium_promo_premium_promo__status_entities). */
final class TlHelpPremiumPromoPremiumPromoStatus_entities extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_help_premium_promo_premium_promo__status_entities';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
