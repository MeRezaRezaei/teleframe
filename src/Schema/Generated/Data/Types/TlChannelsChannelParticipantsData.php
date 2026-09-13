<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Data\Types;

use Spatie\LaravelData\Data;

/** DTO for channels.channelParticipants of channels.ChannelParticipants.
 */
final class TlChannelsChannelParticipantsData extends TlChannelsChannelParticipantsAbstractData
{
    public function __construct(
    public int $count,
    public array $participants,
    public array $chats,
    public array $users,
    ) {
    }
}
