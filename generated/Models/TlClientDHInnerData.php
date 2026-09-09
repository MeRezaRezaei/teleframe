<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type Client_DH_Inner_Data (spec §4.1). */
final class TlClientDHInnerData extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_client__d_h__inner__data';

    protected $guarded = [];
}
