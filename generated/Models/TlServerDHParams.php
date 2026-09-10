<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type Server_DH_Params (spec §4.1). */
final class TlServerDHParams extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_server__d_h__params_server__d_h_params_fail';

    protected $guarded = [];
}
