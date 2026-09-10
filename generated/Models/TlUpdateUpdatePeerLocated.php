<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUpdateUpdatePeerLocatedPeers;

/** Constructor model for updatePeerLocated of Update (crc32 b4afcfb0). */
final class TlUpdateUpdatePeerLocated extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_update_update_peer_located';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlUpdateUpdatePeerLocatedPeers::class);
    }
}
