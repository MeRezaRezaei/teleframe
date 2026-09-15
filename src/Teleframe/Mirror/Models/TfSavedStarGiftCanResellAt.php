<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfSavedStarGiftCanResellAt extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_can_resell_at';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'can_resell_at' => 'int',
    ];

    public function savedGift(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGift::class, 'id', 'id');
    }
}
