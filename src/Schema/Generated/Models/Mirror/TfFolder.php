<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfFolder extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_folders';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'autofill_new_broadcasts' => 'boolean',
        'autofill_public_groups' => 'boolean',
        'autofill_new_correspondents' => 'boolean',
    ];
}
