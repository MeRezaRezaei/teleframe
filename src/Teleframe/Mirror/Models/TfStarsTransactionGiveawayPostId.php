<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfStarsTransactionGiveawayPostId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_giveaway_post_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'giveaway_post_id' => 'int',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
