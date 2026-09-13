<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfSavedStarGift extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_star_gift_id' => 'integer',
        'date' => 'integer',
        'name_hidden' => 'boolean',
        'unsaved' => 'boolean',
        'refunded' => 'boolean',
        'can_upgrade' => 'boolean',
        'pinned_to_top' => 'boolean',
        'upgrade_separate' => 'boolean',
    ];
}
