<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputBotInlineMessageInputBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputMediaInputMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStarGift;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceiptStars;

/** Anchor model for TL type Invoice (spec §4.1). */
final class TlInvoice extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_invoice';

    protected $guarded = [];

    public function invoice(): HasMany
    {
        return $this->hasMany(TlInputMediaInputMediaInvoice::class, 'invoice');
    }
    public function invoiceInputBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlInputBotInlineMessageInputBotInlineMessageMediaInvoice::class, 'invoice');
    }
    public function invoicePaymentsPaymentForm(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentForm::class, 'invoice');
    }
    public function invoicePaymentsPaymentFormStarGift(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentFormStarGift::class, 'invoice');
    }
    public function invoicePaymentsPaymentFormStars(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentFormStars::class, 'invoice');
    }
    public function invoicePaymentsPaymentReceipt(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceipt::class, 'invoice');
    }
    public function invoicePaymentsPaymentReceiptStars(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceiptStars::class, 'invoice');
    }
}
