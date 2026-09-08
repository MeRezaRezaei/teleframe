<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesVotesListVotesListVotes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesVotesListVotesListChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlMessagesVotesListVotesListUsers;

/** Constructor model for messages.votesList of messages.VotesList (crc32 4899484e). */
final class TlMessagesVotesListVotesList extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_messages_votes_list_votes_list';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'count' => 'int',
        'next_offset' => 'string',
    ];

    public function votes(): HasMany
    {
        return $this->tlChild(TlMessagesVotesListVotesListVotes::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlMessagesVotesListVotesListChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlMessagesVotesListVotesListUsers::class);
    }
}
