<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfMessageReplyTo extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_messages_reply_to';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'id' => 'integer',
        'reply_to_scheduled' => 'boolean',
        'forum_topic' => 'boolean',
        'quote' => 'boolean',
        'reply_to_ephemeral' => 'boolean',
        'reply_to_msg_id' => 'integer',
        'reply_to_peer_id_type' => 'integer',
        'reply_to_peer_id_id' => 'integer',
        'reply_to_top_id' => 'integer',
        'quote_offset' => 'integer',
        'todo_item_id' => 'integer',
        'peer_type' => 'integer',
        'peer_id' => 'integer',
        'story_id' => 'integer',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(TfMessage::class, 'id', 'id');
    }
}
