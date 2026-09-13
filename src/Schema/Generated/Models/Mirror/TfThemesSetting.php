<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfThemesSetting extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_themes_settings';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'message_colors_animated' => 'boolean',
        'accent_color' => 'integer',
        'outbox_accent_color' => 'integer',
    ];
}
