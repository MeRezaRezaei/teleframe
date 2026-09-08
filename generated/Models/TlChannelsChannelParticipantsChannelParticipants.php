<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantsChannelParticipantsParticipants;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantsChannelParticipantsChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantsChannelParticipantsUsers;

/** Constructor model for channels.channelParticipants of channels.ChannelParticipants (crc32 9ab0feaf). */
final class TlChannelsChannelParticipantsChannelParticipants extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channels_channel_participants_channel_participants';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function participants(): HasMany
    {
        return $this->tlChild(TlChannelsChannelParticipantsChannelParticipantsParticipants::class);
    }
    public function chats(): HasMany
    {
        return $this->tlChild(TlChannelsChannelParticipantsChannelParticipantsChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChannelsChannelParticipantsChannelParticipantsUsers::class);
    }
}
