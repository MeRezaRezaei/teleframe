<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfDialogFilter extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_dialog_filters';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'contacts' => 'boolean',
        'non_contacts' => 'boolean',
        'groups' => 'boolean',
        'broadcasts' => 'boolean',
        'bots' => 'boolean',
        'exclude_muted' => 'boolean',
        'exclude_read' => 'boolean',
        'exclude_archived' => 'boolean',
        'title_noanimate' => 'boolean',
    ];
}
