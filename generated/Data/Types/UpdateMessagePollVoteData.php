<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for updateMessagePollVote of Update.
 */
final class UpdateMessagePollVoteData extends TlUpdateAbstractData
{
    public function __construct(
    public int $pollId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlPeerAbstractData $peer,
    public array $options,
    public array $positions,
    public int $qts,
    ) {
    }
}
