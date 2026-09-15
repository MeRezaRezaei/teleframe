<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 starref_amount child — flags.17?StarsAmount union, flattened.
 */
final class TfStarsTransactionStarrefAmount extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_starref_amount';

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
