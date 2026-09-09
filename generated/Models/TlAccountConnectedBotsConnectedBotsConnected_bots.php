<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param connected_bots (table tl_account_connected_bots_connected_bots__connected_bots). */
final class TlAccountConnectedBotsConnectedBotsConnected_bots extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_account_connected_bots_connected_bots__connected_bots';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
