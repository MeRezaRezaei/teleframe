<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPaymentRefunded;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessageActionMessageActionPaymentSentMe;

/** Anchor model for TL type PaymentCharge (spec §4.1). */
final class TlPaymentCharge extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_payment_charge_payment_charge';

    protected $guarded = [];

    public function charge(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPaymentSentMe::class, 'charge');
    }
    public function chargeMessageActionPaymentRefunded(): HasMany
    {
        return $this->hasMany(TlMessageActionMessageActionPaymentRefunded::class, 'charge');
    }
}
