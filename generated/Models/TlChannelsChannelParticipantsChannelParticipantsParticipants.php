<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param participants (table tl_channels_channel_participants_channel_part_6b6c9e490b25). */
final class TlChannelsChannelParticipantsChannelParticipantsParticipants extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_channel_participants_channel_part_6b6c9e490b25';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
