<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for messagePeerVoteInputOption of MessagePeerVote (crc32 74cda504). */
final class TlMessagePeerVoteMessagePeerVoteInputOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_message_peer_vote_message_peer_vote_input_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
        'date' => 'int',
    ];
}
