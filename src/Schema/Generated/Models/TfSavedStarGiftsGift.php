<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_saved_star_gifts_gift`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfSavedStarGiftsGift extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_saved_star_gifts_gift';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, id),
     * which the database enforces; `id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'id';

    public $timestamps = false;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'account_id' => 'int',
        'id' => 'int',
        'constructor' => 'string',
        'limited' => 'boolean',
        'sold_out' => 'boolean',
        'birthday' => 'boolean',
        'require_premium' => 'boolean',
        'limited_per_user' => 'boolean',
        'peer_color_available' => 'boolean',
        'auction' => 'boolean',
        'resale_ton_only' => 'boolean',
        'theme_available' => 'boolean',
        'burned' => 'boolean',
        'crafted' => 'boolean',
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
        'title' => 'string',
        'released_by_type' => 'int',
        'released_by_id' => 'int',
        'per_user_total' => 'int',
        'per_user_remains' => 'int',
        'locked_until_date' => 'int',
        'auction_slug' => 'string',
        'gifts_per_round' => 'int',
        'auction_start_date' => 'int',
        'upgrade_variants' => 'int',
        'slug' => 'string',
        'num' => 'int',
        'owner_id_type' => 'int',
        'owner_id_id' => 'int',
        'owner_name' => 'string',
        'owner_address' => 'string',
        'gift_address' => 'string',
        'value_amount' => 'int',
        'value_currency' => 'string',
        'value_usd_amount' => 'int',
        'theme_peer_type' => 'int',
        'theme_peer_id' => 'int',
        'offer_min_stars' => 'int',
        'craft_chance_permille' => 'int',
        'availability_issued' => 'int',
        'host_id_type' => 'int',
        'host_id_id' => 'int',
    ];

    /** Declared foreign key: `tf_saved_star_gifts_gift`(account_id, id) -> `tf_saved_star_gifts`(account_id, id). Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function savedStarGifts(): BelongsTo
    {
        return $this->belongsTo(TfSavedStarGifts::class, 'id', 'id');
    }
}
