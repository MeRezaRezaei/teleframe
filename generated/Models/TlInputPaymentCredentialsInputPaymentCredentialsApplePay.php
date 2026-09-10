<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDataJSON;

/** Constructor model for inputPaymentCredentialsApplePay of InputPaymentCredentials (crc32 0aa1c39f). */
final class TlInputPaymentCredentialsInputPaymentCredentialsApplePay extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_payment_credentials_input_payment_cr_cf69945d7b14';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function paymentData(): BelongsTo
    {
        return $this->belongsTo(TlDataJSON::class, 'payment_data');
    }
}
