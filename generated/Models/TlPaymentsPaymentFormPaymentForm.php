<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormAdditional_methods;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormSaved_credentials;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormUsers;

/** Constructor model for payments.paymentForm of payments.PaymentForm (crc32 a0058751). */
final class TlPaymentsPaymentFormPaymentForm extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payments_payment_form_payment_form';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_save_credentials' => 'bool',
        'password_missing' => 'bool',
        'form_id' => 'int',
        'bot_id' => 'int',
        'title' => 'string',
        'description' => 'string',
        'photo' => 'string',
        'invoice' => 'string',
        'provider_id' => 'int',
        'url' => 'string',
        'native_provider' => 'string',
        'native_params' => 'string',
        'saved_info' => 'string',
    ];

    public function additionalMethods(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentFormPaymentFormAdditional_methods::class);
    }
    public function savedCredentials(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentFormPaymentFormSaved_credentials::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsPaymentFormPaymentFormUsers::class);
    }
}
