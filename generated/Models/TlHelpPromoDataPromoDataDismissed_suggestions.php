<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param dismissed_suggestions (table tl_help_promo_data_promo_data__dismissed_suggestions). */
final class TlHelpPromoDataPromoDataDismissed_suggestions extends TlAnchorModel
{
    protected $table = 'tl_help_promo_data_promo_data__dismissed_suggestions';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
