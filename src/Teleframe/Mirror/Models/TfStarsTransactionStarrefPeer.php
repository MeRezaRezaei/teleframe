<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 starref_peer child — flags.17?Peer of the star-ref program, inline pair.
 */
final class TfStarsTransactionStarrefPeer extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_starref_peer';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'starref_peer_type' => 'int',
        'starref_peer_id' => 'int',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
