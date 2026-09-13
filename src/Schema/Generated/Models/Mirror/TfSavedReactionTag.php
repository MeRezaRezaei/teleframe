<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfSavedReactionTag extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_reaction_tags';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_reaction_tag_id' => 'integer',
        'count' => 'integer',
    ];
}
