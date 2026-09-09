<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlHelpPeerColorSetPeerColorSetColors;

/** Constructor model for help.peerColorSet of help.PeerColorSet (crc32 26219a58). */
final class TlHelpPeerColorSetPeerColorSet extends TlInstanceModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_help_peer_color_set_peer_color_set';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function colors(): HasMany
    {
        return $this->tlChild(TlHelpPeerColorSetPeerColorSetColors::class);
    }
}
