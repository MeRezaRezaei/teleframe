<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for requestedPeerChat of RequestedPeer (crc32 7307544f). */
final class TlRequestedPeerRequestedPeerChat extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_requested_peer_requested_peer_chat';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'chat_id' => 'int',
        'title' => 'string',
        'photo' => 'string',
    ];
}
