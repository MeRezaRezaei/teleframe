<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for payments.exportedInvoice of payments.ExportedInvoice (crc32 aed0cbd9). */
final class TlPaymentsExportedInvoiceExportedInvoice extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_exported_invoice_exported_invoice';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'url' => 'string',
    ];
}
