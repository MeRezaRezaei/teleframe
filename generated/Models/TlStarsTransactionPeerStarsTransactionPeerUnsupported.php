<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsTransactionPeerUnsupported of StarsTransactionPeer (crc32 95f2bfe4). */
final class TlStarsTransactionPeerStarsTransactionPeerUnsupported extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_transaction_peer_stars_transaction_p_dea11222315a';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
