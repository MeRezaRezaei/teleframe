<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Anchor model for TL type InputInvoice (spec §4.1). */
final class TlInputInvoice extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_invoice_input_invoice_business_bot_transfer_stars';

    protected $guarded = [];
}
