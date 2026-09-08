<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsSubscription of StarsSubscription (crc32 2e6eab1a). */
final class TlStarsSubscriptionStarsSubscription extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_subscription_stars_subscription';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'canceled' => 'bool',
        'can_refulfill' => 'bool',
        'missing_balance' => 'bool',
        'bot_canceled' => 'bool',
        'tl_id' => 'string',
        'peer' => 'string',
        'until_date' => 'int',
        'pricing' => 'string',
        'chat_invite_hash' => 'string',
        'title' => 'string',
        'photo' => 'string',
        'invoice_slug' => 'string',
    ];
}
