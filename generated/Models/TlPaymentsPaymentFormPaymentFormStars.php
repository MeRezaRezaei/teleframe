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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStarsUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for payments.paymentFormStars of payments.PaymentForm (crc32 7bf6b15c). */
final class TlPaymentsPaymentFormPaymentFormStars extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_payment_form_payment_form_stars';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'form_id' => 'int',
        'bot_id' => 'int',
        'title' => 'string',
        'description' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentFormPaymentFormStarsUsers::class);
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
