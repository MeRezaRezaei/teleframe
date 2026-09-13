<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogsDraft extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_draft';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'date' => 'integer',
        'no_webpage' => 'boolean',
        'invert_media' => 'boolean',
        'effect' => 'integer',
    ];
}
