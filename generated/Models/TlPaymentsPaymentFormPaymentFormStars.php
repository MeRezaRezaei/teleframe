<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormStarsUsers;

/** Constructor model for payments.paymentFormStars of payments.PaymentForm (crc32 7bf6b15c). */
final class TlPaymentsPaymentFormPaymentFormStars extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_payment_form_payment_form_stars';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'form_id' => 'int',
        'bot_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'invoice' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentFormPaymentFormStarsUsers::class);
    }
}
