<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for updateReadChannelDiscussionInbox of Update (crc32 d6b19546). */
final class TlUpdateUpdateReadChannelDiscussionInbox extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_read_channel_discussion_inbox';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'channel_id' => 'int',
        'top_msg_id' => 'int',
        'read_max_id' => 'int',
        'broadcast_id' => 'int',
        'broadcast_post' => 'int',
    ];
}
