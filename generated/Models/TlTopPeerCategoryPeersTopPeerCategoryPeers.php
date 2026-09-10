<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTopPeerCategory;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlTopPeerCategoryPeersTopPeerCategoryPeersPeers;

/** Constructor model for topPeerCategoryPeers of TopPeerCategoryPeers (crc32 fb834291). */
final class TlTopPeerCategoryPeersTopPeerCategoryPeers extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;

    protected $table = 'tl_top_peer_category_peers_top_peer_category_peers';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'count' => 'int',
    ];

    public function peers(): HasMany
    {
        return $this->tlChild(TlTopPeerCategoryPeersTopPeerCategoryPeersPeers::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TlTopPeerCategory::class, 'category');
    }
}
