<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPeerColorSetPeerColorProfileSetBg_colors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPeerColorSetPeerColorProfileSetPalette_colors;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPeerColorSetPeerColorProfileSetStory_colors;

/** Constructor model for help.peerColorProfileSet of help.PeerColorSet (crc32 767d61eb). */
final class TlHelpPeerColorSetPeerColorProfileSet extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_peer_color_set_peer_color_profile_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function paletteColors(): HasMany
    {
        return $this->tlChild(TlHelpPeerColorSetPeerColorProfileSetPalette_colors::class);
    }
    public function bgColors(): HasMany
    {
        return $this->tlChild(TlHelpPeerColorSetPeerColorProfileSetBg_colors::class);
    }
    public function storyColors(): HasMany
    {
        return $this->tlChild(TlHelpPeerColorSetPeerColorProfileSetStory_colors::class);
    }
}
