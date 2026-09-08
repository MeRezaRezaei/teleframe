<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messageActionPaymentSentMe of MessageAction (crc32 ffa00ccc). */
final class TlMessageActionMessageActionPaymentSentMe extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_action_message_action_payment_sent_me';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'recurring_init' => 'bool',
        'recurring_used' => 'bool',
        'currency' => 'string',
        'total_amount' => 'int',
        'payload' => 'string',
        'info' => 'string',
        'shipping_option_id' => 'string',
        'charge' => 'string',
        'subscription_until_date' => 'int',
    ];
}
