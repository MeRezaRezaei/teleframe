<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

final class TfDialog extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_dialogs';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'peer_id' => 'integer',
        'top_message' => 'integer',
        'read_inbox_max_id' => 'integer',
        'read_outbox_max_id' => 'integer',
        'unread_count' => 'integer',
        'unread_mentions_count' => 'integer',
        'unread_reactions_count' => 'integer',
        'unread_poll_votes_count' => 'integer',
        'pinned' => 'boolean',
        'unread_mark' => 'boolean',
        'view_forum_as_messages' => 'boolean',
    ];
}
