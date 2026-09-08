<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelParticipantLeft of ChannelParticipant (crc32 1b03f006). */
final class TlChannelParticipantChannelParticipantLeft extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_participant_channel_participant_left';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'peer' => 'string',
    ];
}
