<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumMyBoostsMyBoostsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumMyBoostsMyBoostsMy_boosts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumMyBoostsMyBoostsUsers;

/** Constructor model for premium.myBoosts of premium.MyBoosts (crc32 9ae228e2). */
final class TlPremiumMyBoostsMyBoosts extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_premium_my_boosts_my_boosts';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function myBoosts(): HasMany
    {
        return $this->tlChild(TlPremiumMyBoostsMyBoostsMy_boosts::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlPremiumMyBoostsMyBoostsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPremiumMyBoostsMyBoostsUsers::class);
    }
}
