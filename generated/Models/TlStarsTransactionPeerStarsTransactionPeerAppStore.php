<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsTransactionPeerAppStore of StarsTransactionPeer (crc32 b457b375). */
final class TlStarsTransactionPeerStarsTransactionPeerAppStore extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_transaction_peer_stars_transaction_peer_app_store';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
