<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerResolution;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlDocument;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlStarGiftBackground;

/** Constructor model for starGift of StarGift (crc32 313a9547). */
final class TlStarGiftStarGift extends TlAnchorModel
{
    use HasFactory, HasTlChildren;
    use AccountScoped;
    use PeerResolution;

    protected $table = 'tl_star_gift_star_gift';

    protected $guarded = [];

    /** @var array<string, string> */
    protected $casts = [
        'flags' => 'int',
        'limited' => 'bool',
        'sold_out' => 'bool',
        'birthday' => 'bool',
        'require_premium' => 'bool',
        'limited_per_user' => 'bool',
        'peer_color_available' => 'bool',
        'auction' => 'bool',
        'tl_id' => 'int',
        'stars' => 'int',
        'availability_remains' => 'int',
        'availability_total' => 'int',
        'availability_resale' => 'int',
        'convert_stars' => 'int',
        'first_sale_date' => 'int',
        'last_sale_date' => 'int',
        'upgrade_stars' => 'int',
        'resell_min_stars' => 'int',
        'title' => 'string',
        'per_user_total' => 'int',
        'per_user_remains' => 'int',
        'locked_until_date' => 'int',
        'auction_slug' => 'string',
        'gifts_per_round' => 'int',
        'auction_start_date' => 'int',
        'upgrade_variants' => 'int',
    ];

    public function sticker(): BelongsTo
    {
        return $this->belongsTo(TlDocument::class, 'sticker');
    }
    public function background(): BelongsTo
    {
        return $this->belongsTo(TlStarGiftBackground::class, 'background');
    }
}
