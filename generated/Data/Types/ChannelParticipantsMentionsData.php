<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelParticipantsMentions of ChannelParticipantsFilter.
 */
final class ChannelParticipantsMentionsData extends TlChannelParticipantsFilterAbstractData
{
    public function __construct(
    public int $flags,
    public ?string $q,
    public ?int $topMsgId,
    ) {
    }
}
