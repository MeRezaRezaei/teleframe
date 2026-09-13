<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesServiceReactionsTopReactor extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_reactions_top_reactors';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'top' => 'boolean',
        'my' => 'boolean',
        'anonymous' => 'boolean',
        'peer_id_id' => 'integer',
        'count' => 'integer',
    ];
}
