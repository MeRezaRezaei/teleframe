<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for notifyUsers of NotifyPeer (crc32 b4c83b4c). */
final class TlNotifyPeerNotifyUsers extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_notify_peer_notify_users';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
