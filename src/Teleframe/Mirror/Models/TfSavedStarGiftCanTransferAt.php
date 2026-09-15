<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfSavedStarGiftCanTransferAt extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_can_transfer_at';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'can_transfer_at' => 'int',
    ];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
