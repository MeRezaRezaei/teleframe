<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputPeerChannel of InputPeer (crc32 27bcbbfc). */
final class TlInputPeerInputPeerChannel extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_peer_input_peer_channel';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'channel_id' => 'int',
        'access_hash' => 'int',
    ];
}
