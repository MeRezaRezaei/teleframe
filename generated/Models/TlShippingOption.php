<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsPaymentReceiptPaymentReceipt;

/** Anchor model for TL type ShippingOption (spec §4.1). */
final class TlShippingOption extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_shipping_option_shipping_option';

    protected $guarded = [];

    public function shipping(): HasMany
    {
        return $this->hasMany(TlPaymentsPaymentReceiptPaymentReceipt::class, 'shipping');
    }
}
