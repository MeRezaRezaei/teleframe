<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelAdminLogEventActionParticipantJoinByRequest of ChannelAdminLogEventAction.
 */
final class ChannelAdminLogEventActionParticipantJoinByRequestData extends TlChannelAdminLogEventActionAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlExportedChatInviteAbstractData $invite,
    public int $approvedBy,
    ) {
    }
}
