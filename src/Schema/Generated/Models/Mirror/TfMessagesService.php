<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfMessagesService extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'peer_id_id' => 'integer',
        'date' => 'integer',
        'out' => 'boolean',
        'mentioned' => 'boolean',
        'media_unread' => 'boolean',
        'reactions_are_possible' => 'boolean',
        'silent' => 'boolean',
        'post' => 'boolean',
        'legacy' => 'boolean',
    ];
}
