<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for messages.votesList of messages.VotesList.
 */
final class TlMessagesVotesListData extends TlMessagesVotesListAbstractData
{
    public function __construct(
    public int $flags,
    public int $count,
    public array $votes,
    public array $chats,
    public array $users,
    public ?string $nextOffset,
    ) {
    }
}
