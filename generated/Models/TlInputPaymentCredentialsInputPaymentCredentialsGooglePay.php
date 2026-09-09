<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for inputPaymentCredentialsGooglePay of InputPaymentCredentials (crc32 8ac32801). */
final class TlInputPaymentCredentialsInputPaymentCredentialsGooglePay extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_payment_credentials_input_payment_cr_19f70d5158de';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function paymentToken(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'payment_token');
    }
}
