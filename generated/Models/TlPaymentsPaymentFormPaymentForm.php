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
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInvoice;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormAdditional_methods;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormSaved_credentials;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentFormPaymentFormUsers;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlWebDocument;

/** Constructor model for payments.paymentForm of payments.PaymentForm (crc32 a0058751). */
final class TlPaymentsPaymentFormPaymentForm extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'provider_id' => 'int',
        'url' => 'string',
        'native_provider' => 'string',
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

    public function photo(): BelongsTo
    {
        return $this->belongsTo(TlWebDocument::class, 'photo');
    }
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(TlInvoice::class, 'invoice');
    }
    public function nativeParams(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'native_params');
    }
    public function savedInfo(): BelongsTo
    {
        return $this->belongsTo(TlPaymentRequestedInfo::class, 'saved_info');
    }
}
