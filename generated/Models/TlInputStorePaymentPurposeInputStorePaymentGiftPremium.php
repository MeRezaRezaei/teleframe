<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputStorePaymentGiftPremium of InputStorePaymentPurpose (crc32 616f7fe8). */
final class TlInputStorePaymentPurposeInputStorePaymentGiftPremium extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_e777086dbb72';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'user_id' => 'string',
        'currency' => 'string',
        'amount' => 'int',
    ];
}
