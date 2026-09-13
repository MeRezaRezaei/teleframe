<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfQuickReplie extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_quick_replies';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'shortcut_id' => 'integer',
        'top_message' => 'integer',
        'count' => 'integer',
    ];
}
