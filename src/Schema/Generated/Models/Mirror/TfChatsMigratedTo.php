<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfChatsMigratedTo extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_chats_migrated_to';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'channel_id' => 'integer',
        'access_hash' => 'integer',
        'msg_id' => 'integer',
    ];
}
