<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsStatusBoostsStatusPrepaid_giveaways;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsStatusBoostsStatusMy_boost_slots;

/** Constructor model for premium.boostsStatus of premium.BoostsStatus (crc32 4959427a). */
final class TlPremiumBoostsStatusBoostsStatus extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_premium_boosts_status_boosts_status';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'my_boost' => 'bool',
        'level' => 'int',
        'current_level_boosts' => 'int',
        'boosts' => 'int',
        'gift_boosts' => 'int',
        'next_level_boosts' => 'int',
        'premium_audience' => 'string',
        'boost_url' => 'string',
    ];

    public function prepaidGiveaways(): HasMany
    {
        return $this->tlChild(TlPremiumBoostsStatusBoostsStatusPrepaid_giveaways::class);
    }
    public function myBoostSlots(): HasMany
    {
        return $this->tlChild(TlPremiumBoostsStatusBoostsStatusMy_boost_slots::class);
    }
}
