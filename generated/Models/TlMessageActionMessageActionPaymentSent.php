<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for messageActionPaymentSent of MessageAction (crc32 c624b16e). */
final class TlMessageActionMessageActionPaymentSent extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_payment_sent';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'recurring_init' => 'bool',
        'recurring_used' => 'bool',
        'currency' => 'string',
        'total_amount' => 'int',
        'invoice_slug' => 'string',
        'subscription_until_date' => 'int',
    ];
}
