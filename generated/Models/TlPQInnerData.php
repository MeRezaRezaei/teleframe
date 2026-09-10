<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type P_Q_inner_data (spec §4.1). */
final class TlPQInnerData extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_p__q_inner_data_p_q_inner_data_dc';

    protected $guarded = [];
}
