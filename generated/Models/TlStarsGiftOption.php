<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type StarsGiftOption (spec §4.1). */
final class TlStarsGiftOption extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stars_gift_option';

    protected $guarded = [];
}
