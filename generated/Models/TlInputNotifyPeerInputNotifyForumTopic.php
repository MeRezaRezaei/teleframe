<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for inputNotifyForumTopic of InputNotifyPeer (crc32 5c467992). */
final class TlInputNotifyPeerInputNotifyForumTopic extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_input_notify_peer_input_notify_forum_topic';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'top_msg_id' => 'int',
    ];
}
