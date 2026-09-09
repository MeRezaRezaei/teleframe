<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsListBoostsListBoosts;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPremiumBoostsListBoostsListUsers;

/** Constructor model for premium.boostsList of premium.BoostsList (crc32 86f8613c). */
final class TlPremiumBoostsListBoostsList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_premium_boosts_list_boosts_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function boosts(): HasMany
    {
        return $this->tlChild(TlPremiumBoostsListBoostsListBoosts::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPremiumBoostsListBoostsListUsers::class);
    }
}
