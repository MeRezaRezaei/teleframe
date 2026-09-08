<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelParticipantsContacts of ChannelParticipantsFilter (crc32 bb6ae88d). */
final class TlChannelParticipantsFilterChannelParticipantsContacts extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_participants_filter_channel_partic_c5e6fc6a843c';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'q' => 'string',
    ];
}
