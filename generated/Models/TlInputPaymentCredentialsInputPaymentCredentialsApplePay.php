<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPaymentCredentialsApplePay of InputPaymentCredentials (crc32 0aa1c39f). */
final class TlInputPaymentCredentialsInputPaymentCredentialsApplePay extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_payment_credentials_input_payment_cr_cf69945d7b14';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'payment_data' => 'string',
    ];
}
