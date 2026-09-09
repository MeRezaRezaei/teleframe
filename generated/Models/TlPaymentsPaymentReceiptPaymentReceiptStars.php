<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceiptStarsUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for payments.paymentReceiptStars of payments.PaymentReceipt (crc32 dabbf83a). */
final class TlPaymentsPaymentReceiptPaymentReceiptStars extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_payment_receipt_payment_receipt_stars';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'date' => 'int',
        'bot_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'currency' => 'string',
        'total_amount' => 'int',
        'transaction_id' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentReceiptPaymentReceiptStarsUsers::class);
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TlInvoice::class, 'invoice');
    }
}
