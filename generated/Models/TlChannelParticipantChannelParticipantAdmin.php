<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelParticipantAdmin of ChannelParticipant (crc32 34c3bb53). */
final class TlChannelParticipantChannelParticipantAdmin extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_participant_channel_participant_admin';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'can_edit' => 'bool',
        'self' => 'bool',
        'user_id' => 'int',
        'inviter_id' => 'int',
        'promoted_by' => 'int',
        'date' => 'int',
        'admin_rights' => 'string',
        'rank' => 'string',
    ];
}
