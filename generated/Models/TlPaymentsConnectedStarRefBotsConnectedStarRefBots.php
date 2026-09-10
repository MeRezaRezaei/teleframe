<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsConnectedStarRefBotsConnected73bca7385b9aConnected_bots;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPaymentsConnectedStarRefBotsConnected73bca7385b9aUsers;

/** Constructor model for payments.connectedStarRefBots of payments.ConnectedStarRefBots (crc32 98d5ea1d). */
final class TlPaymentsConnectedStarRefBotsConnectedStarRefBots extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_payments_connected_star_ref_bots_connected_73bca7385b9a';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function connectedBots(): HasMany
    {
        return $this->tlChild(TlPaymentsConnectedStarRefBotsConnected73bca7385b9aConnected_bots::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlPaymentsConnectedStarRefBotsConnected73bca7385b9aUsers::class);
    }
}
