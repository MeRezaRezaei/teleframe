<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionAdsProceedsToDate extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_ads_proceeds_to_date';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'ads_proceeds_to_date' => 'int',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
