<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputInvoicePremiumAuthCode of InputInvoice (crc32 3e77f614). */
final class TlInputInvoiceInputInvoicePremiumAuthCode extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_invoice_input_invoice_premium_auth_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'purpose' => 'string',
    ];
}
