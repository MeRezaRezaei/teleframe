<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for inputNotifyPeer of InputNotifyPeer (crc32 b8bc5b0c). */
final class TlInputNotifyPeerInputNotifyPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_input_notify_peer_input_notify_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
