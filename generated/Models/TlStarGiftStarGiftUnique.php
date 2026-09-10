<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlPeerColor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGiftUniqueAttributes;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftStarGiftUniqueResell_amount;

/** Constructor model for starGiftUnique of StarGift (crc32 85f0a9cd). */
final class TlStarGiftStarGiftUnique extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_star_gift_star_gift_unique';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'require_premium' => 'bool',
        'resale_ton_only' => 'bool',
        'theme_available' => 'bool',
        'burned' => 'bool',
        'crafted' => 'bool',
        'tl_id' => 'int',
        'gift_id' => 'int',
        'title' => 'string',
        'slug' => 'string',
        'num' => 'int',
        'owner_name' => 'string',
        'owner_address' => 'string',
        'availability_issued' => 'int',
        'availability_total' => 'int',
        'gift_address' => 'string',
        'value_amount' => 'int',
        'value_currency' => 'string',
        'value_usd_amount' => 'int',
        'offer_min_stars' => 'int',
        'craft_chance_permille' => 'int',
    ];

    public function attributes(): HasMany
    {
        return $this->tlChild(TlStarGiftStarGiftUniqueAttributes::class);
    }
    public function resellAmount(): HasMany
    {
        return $this->tlChild(TlStarGiftStarGiftUniqueResell_amount::class);
    }

    public function peerColor(): BelongsTo
    {
        return $this->belongsTo(TlPeerColor::class, 'peer_color');
    }
}
