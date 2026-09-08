<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputInvoiceStarGiftDropOriginalDetails of InputInvoice (crc32 0923d8d1). */
final class TlInputInvoiceInputInvoiceStarGiftDropOriginalDetails extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_invoice_input_invoice_star_gift_drop_bd25ad852c6a';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'stargift' => 'string',
    ];
}
