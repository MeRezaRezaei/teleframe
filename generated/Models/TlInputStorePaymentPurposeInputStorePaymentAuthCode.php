<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for inputStorePaymentAuthCode of InputStorePaymentPurpose (crc32 3fc18057). */
final class TlInputStorePaymentPurposeInputStorePaymentAuthCode extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_fcc6e9ee0964';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'restore' => 'bool',
        'phone_number' => 'string',
        'phone_code_hash' => 'string',
        'premium_days' => 'int',
        'currency' => 'string',
        'amount' => 'int',
    ];
}
