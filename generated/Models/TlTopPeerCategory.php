<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTopPeerCategoryPeersTopPeerCategoryPeers;

/** Anchor model for TL type TopPeerCategory (spec §4.1). */
final class TlTopPeerCategory extends TlAnchorModel
{
    use AccountScoped;

    protected $table = 'tl_top_peer_category_top_peer_category_bots_app';

    protected $guarded = [];

    public function category(): HasMany
    {
        return $this->hasMany(TlTopPeerCategoryPeersTopPeerCategoryPeers::class, 'category');
    }
}
