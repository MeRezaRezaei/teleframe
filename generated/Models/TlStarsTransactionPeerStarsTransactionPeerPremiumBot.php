<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for starsTransactionPeerPremiumBot of StarsTransactionPeer (crc32 250dbaf8). */
final class TlStarsTransactionPeerStarsTransactionPeerPremiumBot extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_stars_transaction_peer_stars_transaction_p_812ddc94a9e3';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
