<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceiptUsers;

/** Constructor model for payments.paymentReceipt of payments.PaymentReceipt (crc32 70c4fe03). */
final class TlPaymentsPaymentReceiptPaymentReceipt extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

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
        'photo' => 'string',
        'invoice' => 'string',
        'info' => 'string',
        'shipping' => 'string',
        'tip_amount' => 'int',
        'currency' => 'string',
        'total_amount' => 'int',
        'credentials_title' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentReceiptPaymentReceiptUsers::class);
    }
}
