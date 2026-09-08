<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;

/** Vector child rows for param channels (table tl_message_media_message_media_giveaway__channels). */
final class TlMessageMediaMessageMediaGiveawayChannels extends TlAnchorModel
{
    protected $table = 'tl_message_media_message_media_giveaway__channels';

    public $timestamps = false; // child tables carry no timestamps columns

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'value' => 'int',
    ];
}
