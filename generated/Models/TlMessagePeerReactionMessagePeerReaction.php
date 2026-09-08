<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messagePeerReaction of MessagePeerReaction (crc32 8c79b63c). */
final class TlMessagePeerReactionMessagePeerReaction extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_peer_reaction_message_peer_reaction';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'big' => 'bool',
        'unread' => 'bool',
        'my' => 'bool',
        'peer_id' => 'string',
        'date' => 'int',
        'reaction' => 'string',
    ];
}
