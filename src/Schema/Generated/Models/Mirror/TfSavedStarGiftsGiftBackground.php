<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfSavedStarGiftsGiftBackground extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_gift_background';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_star_gift_id' => 'integer',
        'center_color' => 'integer',
        'edge_color' => 'integer',
        'text_color' => 'integer',
    ];
}
