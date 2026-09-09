<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfoPaymentRequestedInfo;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdateBotShippingQuery;

/** Anchor model for TL type PostAddress (spec §4.1). */
final class TlPostAddress extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_post_address';

    protected $guarded = [];

    public function shippingAddress(): HasMany
    {
        return $this->hasMany(TlUpdateUpdateBotShippingQuery::class, 'shipping_address');
    }
    public function shippingAddressPaymentRequestedInfo(): HasMany
    {
        return $this->hasMany(TlPaymentRequestedInfoPaymentRequestedInfo::class, 'shipping_address');
    }
}
