<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

/**
 * Telegram dialog (chat list entry) — tf_dialogs.
 *
 * PK: (peer_id, peer_type, account_id). Dialogs have no Telegram-assigned
 * numeric ID — they are identified by the (peer, account) pair.
 *
 * peer_type is one of: 'peerUser', 'peerChat', 'peerChannel'.
 */
class TfDialog extends TfModel
{
    protected $table = 'tf_dialogs';

    /** Composite PK — Eloquent uses peer_id as logical key. */
    protected $primaryKey = 'peer_id';

    /** @var list<string> */
    protected $fillable = [
        'peer_id', 'peer_type', 'account_id', 'top_message_id',
        'read_inbox_max_id', 'read_outbox_max_id', 'unread_count',
        'unread_mentions_count', 'unread_reactions_count', 'folder_id',
        'pts',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'tl_data' => 'array',
        'is_pinned' => 'boolean',
        'is_unread_mark' => 'boolean',
    ];
}
