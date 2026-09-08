<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starsTransactionPeerAds of StarsTransactionPeer (crc32 60682812). */
final class TlStarsTransactionPeerStarsTransactionPeerAds extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_stars_transaction_peer_stars_transaction_peer_ads';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
