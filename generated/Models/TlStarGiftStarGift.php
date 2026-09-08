<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use MeRezaRezaei\Teleframe\Schema\Eloquent\HasTlChildren;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlInstanceModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Constructor model for starGift of StarGift (crc32 313a9547). */
final class TlStarGiftStarGift extends TlInstanceModel
{
    use HasFactory, HasTlChildren;

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
        'sticker' => 'string',
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
        'released_by' => 'string',
        'per_user_total' => 'int',
        'per_user_remains' => 'int',
        'locked_until_date' => 'int',
        'auction_slug' => 'string',
        'gifts_per_round' => 'int',
        'auction_start_date' => 'int',
        'upgrade_variants' => 'int',
        'background' => 'string',
    ];
}
