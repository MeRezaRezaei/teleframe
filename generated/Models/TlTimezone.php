<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type Timezone (spec §4.1). */
final class TlTimezone extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_timezone_timezone';

    protected $guarded = [];
}
