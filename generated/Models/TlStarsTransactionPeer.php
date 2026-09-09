<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarsTransactionStarsTransaction;

/** Anchor model for TL type StarsTransactionPeer (spec §4.1). */
final class TlStarsTransactionPeer extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_stars_transaction_peer';

    protected $guarded = [];

    public function peer(): HasMany
    {
        return $this->hasMany(TlStarsTransactionStarsTransaction::class, 'peer');
    }
}
