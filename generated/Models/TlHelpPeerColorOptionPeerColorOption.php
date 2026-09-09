<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPeerColorSet;

/** Constructor model for help.peerColorOption of help.PeerColorOption (crc32 adec6ebe). */
final class TlHelpPeerColorOptionPeerColorOption extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_peer_color_option_peer_color_option';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'hidden' => 'bool',
        'color_id' => 'int',
        'channel_min_level' => 'int',
        'group_min_level' => 'int',
    ];

    public function colors(): BelongsTo
    {
        return $this->belongsTo(TlHelpPeerColorSet::class, 'colors');
    }
    public function darkColors(): BelongsTo
    {
        return $this->belongsTo(TlHelpPeerColorSet::class, 'dark_colors');
    }
}
