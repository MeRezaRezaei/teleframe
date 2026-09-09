<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPaymentSentMe;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentForm;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsSavedInfoSavedInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotPrecheckoutQuery;

/** Anchor model for TL type PaymentRequestedInfo (spec §4.1). */
final class TlPaymentRequestedInfo extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payment_requested_info';

    protected $guarded = [];

    public function info(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPaymentSentMe::class, 'info');
    }
    public function infoPaymentsPaymentReceipt(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceipt::class, 'info');
    }
    public function infoUpdateBotPrecheckoutQuery(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotPrecheckoutQuery::class, 'info');
    }
    public function savedInfo(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentFormPaymentForm::class, 'saved_info');
    }
    public function savedInfoPaymentsSavedInfo(): HasMany
    {
        return $this->hasMany(TlPaymentsSavedInfoSavedInfo::class, 'saved_info');
    }
}
