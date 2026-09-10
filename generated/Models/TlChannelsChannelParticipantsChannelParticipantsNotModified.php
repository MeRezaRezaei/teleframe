<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Constructor model for channels.channelParticipantsNotModified of channels.ChannelParticipants (crc32 f0173fe9). */
final class TlChannelsChannelParticipantsChannelParticipantsNotModified extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channels_channel_participants_channel_part_453012fa781f';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
