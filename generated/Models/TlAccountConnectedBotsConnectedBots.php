<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountConnectedBotsConnectedBotsConnected_bots;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlAccountConnectedBotsConnectedBotsUsers;

/** Constructor model for account.connectedBots of account.ConnectedBots (crc32 17d7f87b). */
final class TlAccountConnectedBotsConnectedBots extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_account_connected_bots_connected_bots';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function connectedBots(): HasMany
    {
        return $this->tlChild(TlAccountConnectedBotsConnectedBotsConnected_bots::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlAccountConnectedBotsConnectedBotsUsers::class);
    }
}
