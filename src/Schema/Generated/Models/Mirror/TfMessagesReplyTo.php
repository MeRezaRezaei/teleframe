<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessagesReplyTo extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_to';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'reply_to_scheduled' => 'boolean',
        'forum_topic' => 'boolean',
        'quote' => 'boolean',
        'reply_to_ephemeral' => 'boolean',
        'reply_to_msg_id' => 'integer',
        'reply_to_peer_id_id' => 'integer',
        'reply_to_top_id' => 'integer',
        'quote_offset' => 'integer',
        'todo_item_id' => 'integer',
        'peer_id' => 'integer',
        'story_id' => 'integer',
    ];
}
