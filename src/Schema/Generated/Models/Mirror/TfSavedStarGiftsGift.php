<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models\Mirror;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

final class TfSavedStarGiftsGift extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_gift';
    protected $primaryKey = 'parent_id';

    protected $guarded = [];

    protected $casts = [
        'account_id' => 'integer',
        'saved_star_gift_id' => 'integer',
        'limited' => 'boolean',
        'sold_out' => 'boolean',
        'birthday' => 'boolean',
        'require_premium' => 'boolean',
        'limited_per_user' => 'boolean',
        'peer_color_available' => 'boolean',
        'auction' => 'boolean',
        'id' => 'integer',
        'stars' => 'integer',
        'availability_remains' => 'integer',
        'availability_total' => 'integer',
        'availability_resale' => 'integer',
        'convert_stars' => 'integer',
        'first_sale_date' => 'integer',
        'last_sale_date' => 'integer',
        'upgrade_stars' => 'integer',
        'resell_min_stars' => 'integer',
        'released_by_id' => 'integer',
        'per_user_total' => 'integer',
        'per_user_remains' => 'integer',
        'locked_until_date' => 'integer',
        'gifts_per_round' => 'integer',
        'auction_start_date' => 'integer',
        'upgrade_variants' => 'integer',
        'resale_ton_only' => 'boolean',
        'theme_available' => 'boolean',
        'burned' => 'boolean',
        'crafted' => 'boolean',
        'gift_id' => 'integer',
        'num' => 'integer',
        'owner_id_id' => 'integer',
        'availability_issued' => 'integer',
        'value_amount' => 'integer',
        'value_usd_amount' => 'integer',
        'theme_peer_id' => 'integer',
        'host_id_id' => 'integer',
        'offer_min_stars' => 'integer',
        'craft_chance_permille' => 'integer',
    ];
}
