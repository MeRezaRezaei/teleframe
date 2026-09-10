<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoicePremiumAuthCode;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoicePremiumGiftCode;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputInvoiceInputInvoiceStars;

/** Anchor model for TL type InputStorePaymentPurpose (spec §4.1). */
final class TlInputStorePaymentPurpose extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_fcc6e9ee0964';

    protected $guarded = [];

    public function purpose(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoicePremiumGiftCode::class, 'purpose');
    }
    public function purposeInputInvoicePremiumAuthCode(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoicePremiumAuthCode::class, 'purpose');
    }
    public function purposeInputInvoiceStars(): HasMany
    {
        return $this->hasMany(TlInputInvoiceInputInvoiceStars::class, 'purpose');
    }
}
