<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channelParticipantCreator of ChannelParticipant.
 */
final class ChannelParticipantCreatorData extends TlChannelParticipantAbstractData
{
    public function __construct(
    public int $flags,
    public int $userId,
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChatAdminRightsAbstractData $adminRights,
    public ?string $rank,
    ) {
    }
}
