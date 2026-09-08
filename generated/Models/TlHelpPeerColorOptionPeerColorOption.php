<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for help.peerColorOption of help.PeerColorOption (crc32 adec6ebe). */
final class TlHelpPeerColorOptionPeerColorOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

    protected $table = 'tl_help_peer_color_option_peer_color_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'hidden' => 'bool',
        'color_id' => 'int',
        'colors' => 'string',
        'dark_colors' => 'string',
        'channel_min_level' => 'int',
        'group_min_level' => 'int',
    ];
}
