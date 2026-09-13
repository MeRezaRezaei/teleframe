<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfDialogsDraftEntity extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs_draft_entities';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'offset' => 'integer',
        'length' => 'integer',
        'user_id' => 'integer',
        'document_id' => 'integer',
        'collapsed' => 'boolean',
        'relative' => 'boolean',
        'short_time' => 'boolean',
        'long_time' => 'boolean',
        'short_date' => 'boolean',
        'long_date' => 'boolean',
        'day_of_week' => 'boolean',
        'date' => 'integer',
    ];
}
