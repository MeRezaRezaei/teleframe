<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionParticipantEditRank of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionParticipantEditRankData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public int $userId,
    public string $prevRank,
    public string $newRank,
    ) {
    }
}
