<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param countries_iso2 (table tl_input_store_payment_purpose_input_store_pa_94f902dbf3fa). */
final class TlInputStorePaymentPurposeInputStorePaAb10defc70e9Countries_iso2 extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_94f902dbf3fa';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'string',
    ];
}
