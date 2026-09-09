<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param chats (table tl_channels_channel_participants_channel_part_10e2c32cd676). */
final class TlChannelsChannelParticipantsChannelParticipantsChats extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_channels_channel_participants_channel_part_10e2c32cd676';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];
}
