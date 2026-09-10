<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for premiumSubscriptionOption of PremiumSubscriptionOption (crc32 5f2d1df2). */
final class TlPremiumSubscriptionOptionPremiumSubscriptionOption extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_premium_subscription_option_premium_subscription_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'tl_current' => 'bool',
        'can_purchase_upgrade' => 'bool',
        'transaction' => 'string',
        'months' => 'int',
        'currency' => 'string',
        'amount' => 'int',
        'bot_url' => 'string',
        'store_product' => 'string',
    ];
}
