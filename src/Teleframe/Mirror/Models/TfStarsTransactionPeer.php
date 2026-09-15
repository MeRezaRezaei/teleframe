<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 peer child — the required StarsTransactionPeer union (8 ctors); only the
 * starsTransactionPeer ctor carries the Payload peer pair.
 */
final class TfStarsTransactionPeer extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_peer';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'peer_type' => 'int',
        'peer_id' => 'int',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
