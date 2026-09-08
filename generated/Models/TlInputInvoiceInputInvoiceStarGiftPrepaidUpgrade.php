<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputInvoiceStarGiftPrepaidUpgrade of InputInvoice (crc32 9a0b48b8). */
final class TlInputInvoiceInputInvoiceStarGiftPrepaidUpgrade extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_prepaid_upgrade';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'hash' => 'string',
    ];
}
