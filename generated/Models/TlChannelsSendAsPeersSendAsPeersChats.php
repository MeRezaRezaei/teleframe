<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_channels_send_as_peers_send_as_peers__chats). */
final class TlChannelsSendAsPeersSendAsPeersChats extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_send_as_peers_send_as_peers__chats';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
