<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdates;

/** Constructor model for payments.paymentResult of payments.PaymentResult (crc32 4e5f810d). */
final class TlPaymentsPaymentResultPaymentResult extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_payment_result_payment_result';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function updates(): BelongsTo
    {
        return $this->belongsTo(TlUpdates::class, 'updates');
    }
}
