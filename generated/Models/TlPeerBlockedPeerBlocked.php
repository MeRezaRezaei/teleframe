<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for peerBlocked of PeerBlocked (crc32 e8fd8014). */
final class TlPeerBlockedPeerBlocked extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_peer_blocked_peer_blocked';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer_id' => 'string',
        'date' => 'int',
    ];
}
