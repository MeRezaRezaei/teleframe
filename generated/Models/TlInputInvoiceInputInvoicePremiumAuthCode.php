<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputStorePaymentPurpose;

/** Constructor model for inputInvoicePremiumAuthCode of InputInvoice (crc32 3e77f614). */
final class TlInputInvoiceInputInvoicePremiumAuthCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_invoice_input_invoice_premium_auth_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(TlInputStorePaymentPurpose::class, 'purpose');
    }
}
