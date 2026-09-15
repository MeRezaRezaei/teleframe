<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 amount child — the required StarsAmount union (starsAmount |
 * starsTonAmount), flattened to amount/nanos.
 */
final class TfStarsTransactionAmount extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_amount';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'amount' => 'int',
        'nanos' => 'int',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
