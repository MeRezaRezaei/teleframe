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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumGiftCodeOption;

/** Constructor model for inputInvoicePremiumGiftCode of InputInvoice (crc32 98986c0d). */
final class TlInputInvoiceInputInvoicePremiumGiftCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_invoice_input_invoice_premium_gift_code';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(TlInputStorePaymentPurpose::class, 'purpose');
    }
    public function option(): BelongsTo
    {
        return $this->belongsTo(TlPremiumGiftCodeOption::class, 'option');
    }
}
