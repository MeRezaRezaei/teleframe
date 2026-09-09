<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlInputUser;

/** Constructor model for inputStorePaymentGiftPremium of InputStorePaymentPurpose (crc32 616f7fe8). */
final class TlInputStorePaymentPurposeInputStorePaymentGiftPremium extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_input_store_payment_purpose_input_store_pa_e777086dbb72';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'currency' => 'string',
        'amount' => 'int',
    ];

    public function userId(): BelongsTo
    {
        return $this->belongsTo(TlInputUser::class, 'user_id');
    }
}
