<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelParticipant of ChannelParticipant.
 */
final class ChannelParticipantData extends TlChannelParticipantAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public int $date,
    public ?int $subscriptionUntilDate,
    public ?string $rank,
    ) {
    }
}
