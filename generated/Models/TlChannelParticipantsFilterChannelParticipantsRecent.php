<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for channelParticipantsRecent of ChannelParticipantsFilter (crc32 de3f3c79). */
final class TlChannelParticipantsFilterChannelParticipantsRecent extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_channel_participants_filter_channel_participants_recent';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
