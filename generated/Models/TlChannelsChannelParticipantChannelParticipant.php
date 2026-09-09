<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelParticipant;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantChannelParticipantChats;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlChannelsChannelParticipantChannelParticipantUsers;

/** Constructor model for channels.channelParticipant of channels.ChannelParticipant (crc32 dfb80317). */
final class TlChannelsChannelParticipantChannelParticipant extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_channel_participant_channel_participant';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function chats(): HasMany
    {
        return $this->tlChild(TlChannelsChannelParticipantChannelParticipantChats::class);
    }
    public function users(): HasMany
    {
        return $this->tlChild(TlChannelsChannelParticipantChannelParticipantUsers::class);
    }

    public function participant(): BelongsTo
    {
        return $this->belongsTo(TlChannelParticipant::class, 'participant');
    }
}
