<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for paymentRequestedInfo of PaymentRequestedInfo (crc32 909c3f94). */
final class TlPaymentRequestedInfoPaymentRequestedInfo extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_payment_requested_info_payment_requested_info';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'name' => 'string',
        'phone' => 'string',
        'email' => 'string',
        'shipping_address' => 'string',
    ];
}
