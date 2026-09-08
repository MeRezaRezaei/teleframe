<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for sendAsPeer of SendAsPeer (crc32 b81c7034). */
final class TlSendAsPeerSendAsPeer extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_send_as_peer_send_as_peer';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'premium_required' => 'bool',
        'peer' => 'string',
    ];
}
