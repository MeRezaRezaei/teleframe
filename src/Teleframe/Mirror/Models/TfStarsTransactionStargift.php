<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfChildModel;

/**
 * 1:1 stargift child — flags.14?StarGift union (starGift | starGiftUnique),
 * flattened; the inner gift id is deferred (the child id is the transaction
 * key — plain-name collision under the flat path).
 */
final class TfStarsTransactionStargift extends TfChildModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions_stargift';

    protected $primaryKey = 'id';

    protected $guarded = [];

    protected $casts = [
        'gift_id' => 'int',
        'stars' => 'int',
        'availability_remains' => 'int',
        'availability_total' => 'int',
        'availability_resale' => 'int',
        'convert_stars' => 'int',
        'first_sale_date' => 'int',
        'last_sale_date' => 'int',
        'upgrade_stars' => 'int',
        'resell_min_stars' => 'int',
        'released_by_type' => 'int',
        'released_by_id' => 'int',
        'per_user_total' => 'int',
        'per_user_remains' => 'int',
        'locked_until_date' => 'int',
        'gifts_per_round' => 'int',
        'auction_start_date' => 'int',
        'upgrade_variants' => 'int',
        'num' => 'int',
        'owner_id_type' => 'int',
        'owner_id_id' => 'int',
        'value_amount' => 'int',
        'value_usd_amount' => 'int',
        'theme_peer_type' => 'int',
        'theme_peer_id' => 'int',
        'offer_min_stars' => 'int',
        'craft_chance_permille' => 'int',
        'limited' => 'bool',
        'sold_out' => 'bool',
        'birthday' => 'bool',
        'require_premium' => 'bool',
        'limited_per_user' => 'bool',
        'peer_color_available' => 'bool',
        'auction' => 'bool',
        'resale_ton_only' => 'bool',
        'theme_available' => 'bool',
        'burned' => 'bool',
        'crafted' => 'bool',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(TfStarsTransaction::class, 'id', 'id');
    }
}
