<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type Set_client_DH_params_answer (spec §4.1). */
final class TlSetClientDHParamsAnswer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_set_client__d_h_params_answer_dh_gen_fail';

    protected $guarded = [];
}
