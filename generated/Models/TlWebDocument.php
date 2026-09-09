<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineMessageBotInlineMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlBotInlineResultBotInlineResult;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageMediaMessageMediaInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceiptStars;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsSubscriptionStarsSubscription;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction;

/** Anchor model for TL type WebDocument (spec §4.1). */
final class TlWebDocument extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_web_document';

    protected $guarded = [];

    public function content(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineResult::class, 'content');
    }
    public function photo(): HasMany
    {
        return $this->hasMany(TlMessageMediaMessageMediaInvoice::class, 'photo');
    }
    public function photoBotInlineMessageMediaInvoice(): HasMany
    {
        return $this->hasMany(TlBotInlineMessageBotInlineMessageMediaInvoice::class, 'photo');
    }
    public function photoPaymentsPaymentForm(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentForm::class, 'photo');
    }
    public function photoPaymentsPaymentFormStars(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentFormStars::class, 'photo');
    }
    public function photoPaymentsPaymentReceipt(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceipt::class, 'photo');
    }
    public function photoPaymentsPaymentReceiptStars(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceiptStars::class, 'photo');
    }
    public function photoStarsSubscription(): HasMany
    {
        return $this->hasMany(TlStarsSubscriptionStarsSubscription::class, 'photo');
    }
    public function photoStarsTransaction(): HasMany
    {
        return $this->hasMany(TlStarsTransactionStarsTransaction::class, 'photo');
    }
    public function thumb(): HasMany
    {
        return $this->hasMany(TlBotInlineResultBotInlineResult::class, 'thumb');
    }
}
