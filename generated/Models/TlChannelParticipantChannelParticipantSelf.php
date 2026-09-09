<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;

/** Constructor model for channelParticipantSelf of ChannelParticipant (crc32 a9478a1a). */
final class TlChannelParticipantChannelParticipantSelf extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_channel_participant_channel_participant_self';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'via_request' => 'bool',
        'user_id' => 'int',
        'inviter_id' => 'int',
        'date' => 'int',
        'subscription_until_date' => 'int',
        'rank' => 'string',
    ];
}
