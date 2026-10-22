<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channels.channelParticipant of channels.ChannelParticipant.
 */
final class TlChannelsChannelParticipantData extends TlChannelsChannelParticipantAbstractData
{
    public function __construct(
    public \MeRezaRezaei\Teleframe\Schema\Generated\Data\Types\TlChannelParticipantAbstractData $participant,
    public array $chats,
    public array $users,
    ) {
    }
}
