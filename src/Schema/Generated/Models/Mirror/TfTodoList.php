<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfTodoList extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_todo_lists';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'todo_list_id' => 'integer',
        'others_can_append' => 'boolean',
        'others_can_complete' => 'boolean',
    ];
}
