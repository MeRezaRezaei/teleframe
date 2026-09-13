<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfUsersPhoto extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_users_photo';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'has_video' => 'boolean',
        'personal' => 'boolean',
        'photo_id' => 'integer',
        'dc_id' => 'integer',
    ];
}
