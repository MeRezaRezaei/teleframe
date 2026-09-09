<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param peers (table tl_message_action_message_action_requested_peer__peers). */
final class TlMessageActionMessageActionRequestedPeerPeers extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_message_action_message_action_requested_peer__peers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
