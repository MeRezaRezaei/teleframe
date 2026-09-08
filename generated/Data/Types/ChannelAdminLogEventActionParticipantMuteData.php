<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionParticipantMute of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionParticipantMuteData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlGroupCallParticipantAbstractData $participant,
    ) {
    }
}
