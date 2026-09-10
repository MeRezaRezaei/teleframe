<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceiptUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlShippingOption;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for payments.paymentReceipt of payments.PaymentReceipt (crc32 70c4fe03). */
final class TlPaymentsPaymentReceiptPaymentReceipt extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_payment_receipt_payment_receipt';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'date' => 'int',
        'bot_id' => 'int',
        'provider_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'tip_amount' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'credentials_title' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentReceiptPaymentReceiptUsers::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TlInvoice::class, 'invoice');
    }
    public function info(): BelongsTo
    {
        return $this->belongsTo(TlPaymentRequestedInfo::class, 'info');
    }
    public function shipping(): BelongsTo
    {
        return $this->belongsTo(TlShippingOption::class, 'shipping');
    }
}
