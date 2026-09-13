<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfAttachMenuBot extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_attach_menu_bots';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'bot_id' => 'integer',
        'inactive' => 'boolean',
        'has_settings' => 'boolean',
        'request_write_access' => 'boolean',
        'show_in_attach_menu' => 'boolean',
        'show_in_side_menu' => 'boolean',
        'side_menu_disclaimer_needed' => 'boolean',
    ];
}
