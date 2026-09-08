<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for requestedPeerChannel of RequestedPeer (crc32 8ba403e4). */
final class TlRequestedPeerRequestedPeerChannel extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_requested_peer_requested_peer_channel';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'channel_id' => 'int',
        'title' => 'string',
        'username' => 'string',
        'photo' => 'string',
    ];
}
