<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for premiumGiftCodeOption of PremiumGiftCodeOption (crc32 257e962b). */
final class TlPremiumGiftCodeOptionPremiumGiftCodeOption extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_premium_gift_code_option_premium_gift_code_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'users' => 'int',
        'months' => 'int',
        'store_product' => 'string',
        'store_quantity' => 'int',
        'currency' => 'string',
        'amount' => 'int',
    ];
}
