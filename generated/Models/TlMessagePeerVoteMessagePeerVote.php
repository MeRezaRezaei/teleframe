<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messagePeerVote of MessagePeerVote (crc32 b6cc2d5c). */
final class TlMessagePeerVoteMessagePeerVote extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_peer_vote_message_peer_vote';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'option' => 'string',
        'date' => 'int',
    ];
}
