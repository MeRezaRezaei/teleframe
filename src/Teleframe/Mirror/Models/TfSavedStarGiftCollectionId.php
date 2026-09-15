<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:N collection_id child — the optional Vector<int> collections, one row per
 * vector slot.
 */
final class TfSavedStarGiftCollectionId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_collection_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'position' => 'int',
        'collection_id' => 'int',
    ];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
