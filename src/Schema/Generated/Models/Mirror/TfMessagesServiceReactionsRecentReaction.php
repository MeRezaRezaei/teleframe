<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesServiceReactionsRecentReaction extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_service_reactions_recent_reactions';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'big' => 'boolean',
        'unread' => 'boolean',
        'my' => 'boolean',
        'peer_id_id' => 'integer',
        'date' => 'integer',
    ];
}
