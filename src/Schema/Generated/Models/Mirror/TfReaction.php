<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfReaction extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_reactions';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'reaction_id' => 'integer',
    ];
}
