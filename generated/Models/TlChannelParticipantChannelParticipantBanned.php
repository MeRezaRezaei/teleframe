<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelParticipantBanned of ChannelParticipant (crc32 d5f0ad91). */
final class TlChannelParticipantChannelParticipantBanned extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_participant_channel_participant_banned';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'left' => 'bool',
        'peer' => 'string',
        'kicked_by' => 'int',
        'date' => 'int',
        'banned_rights' => 'string',
        'rank' => 'string',
    ];
}
