<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for dialog of Dialog (crc32 fc89f7f3). */
final class TlDialogDialog extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_dialog_dialog';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'pinned' => 'bool',
        'unread_mark' => 'bool',
        'view_forum_as_messages' => 'bool',
        'peer' => 'string',
        'top_message' => 'int',
        'read_inbox_max_id' => 'int',
        'read_outbox_max_id' => 'int',
        'unread_count' => 'int',
        'unread_mentions_count' => 'int',
        'unread_reactions_count' => 'int',
        'unread_poll_votes_count' => 'int',
        'notify_settings' => 'string',
        'pts' => 'int',
        'draft' => 'string',
        'folder_id' => 'int',
        'ttl_period' => 'int',
    ];
}
