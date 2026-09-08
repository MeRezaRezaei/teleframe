<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param peers (table tl_channels_send_as_peers_send_as_peers__peers). */
final class TlChannelsSendAsPeersSendAsPeersPeers extends TlAnchorModel
{
    protected $table = 'tl_channels_send_as_peers_send_as_peers__peers';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
