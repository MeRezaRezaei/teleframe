<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 from_id child — the optional Peer as an inline {type, id} pair.
 */
final class TfSavedStarGiftFromId extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_from_id';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'from_id_type' => 'int',
        'from_id_id' => 'int',
    ];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
