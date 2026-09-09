<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param countries_iso2 (table tl_input_store_payment_purpose_input_store_pa_91597f6f2753). */
final class TlInputStorePaymentPurposeInputStorePaA2df1d4d4d93Countries_iso2 extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_91597f6f2753';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
