<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionParticipantSubExtend of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionParticipantSubExtendData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelParticipantAbstractData $prevParticipant,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelParticipantAbstractData $newParticipant,
    ) {
    }
}
