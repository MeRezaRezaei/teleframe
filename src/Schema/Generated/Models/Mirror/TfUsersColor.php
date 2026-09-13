<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfUsersColor extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_color';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'color' => 'integer',
        'background_emoji_id' => 'integer',
        'collectible_id' => 'integer',
        'gift_emoji_id' => 'integer',
        'accent_color' => 'integer',
        'dark_accent_color' => 'integer',
    ];
}
