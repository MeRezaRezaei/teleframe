<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentCharge;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentRequestedInfo;

/** Constructor model for messageActionPaymentSentMe of MessageAction (crc32 ffa00ccc). */
final class TlMessageActionMessageActionPaymentSentMe extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

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
        'shipping_option_id' => 'string',
        'subscription_until_date' => 'int',
    ];

    public function info(): BelongsTo
    {
        return $this->belongsTo(TlPaymentRequestedInfo::class, 'info');
    }
    public function charge(): BelongsTo
    {
        return $this->belongsTo(TlPaymentCharge::class, 'charge');
    }
}
