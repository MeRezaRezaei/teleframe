<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColorCollectibleColors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColorPeerColorCollectibleDark_colors;

/** Constructor model for peerColorCollectible of PeerColor (crc32 b9c0639a). */
final class TlPeerColorPeerColorCollectible extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_peer_color_peer_color_collectible';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'collectible_id' => 'int',
        'gift_emoji_id' => 'int',
        'background_emoji_id' => 'int',
        'accent_color' => 'int',
        'dark_accent_color' => 'int',
    ];

    public function colors(): HasMany
    {
        return $this->tlChild(TlPeerColorPeerColorCollectibleColors::class);
    }
    public function darkColors(): HasMany
    {
        return $this->tlChild(TlPeerColorPeerColorCollectibleDark_colors::class);
    }
}
