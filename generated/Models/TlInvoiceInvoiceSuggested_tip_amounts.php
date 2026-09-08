<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param suggested_tip_amounts (table tl_invoice_invoice__suggested_tip_amounts). */
final class TlInvoiceInvoiceSuggested_tip_amounts extends TlAnchorModel
{
    protected $table = 'tl_invoice_invoice__suggested_tip_amounts';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
