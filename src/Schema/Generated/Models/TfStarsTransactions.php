<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_stars_transactions`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 21.
 */
final class TfStarsTransactions extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_stars_transactions';

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
        'id' => 'string',
        'date' => 'int',
        'refund' => 'boolean',
        'pending' => 'boolean',
        'failed' => 'boolean',
        'gift' => 'boolean',
        'reaction' => 'boolean',
        'stargift_upgrade' => 'boolean',
        'business_transfer' => 'boolean',
        'stargift_resale' => 'boolean',
        'posts_search' => 'boolean',
        'stargift_prepaid_upgrade' => 'boolean',
        'stargift_drop_original_details' => 'boolean',
        'phonegroup_message' => 'boolean',
        'stargift_auction_bid' => 'boolean',
        'offer' => 'boolean',
    ];

    /** Declared foreign key (1:N): `tf_stars_transactions_ads_proceeds_from_date`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsAdsProceedsFromDate(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsAdsProceedsFromDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_ads_proceeds_to_date`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsAdsProceedsToDate(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsAdsProceedsToDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_amount`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsAmount(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsAmount::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_bot_payload`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsBotPayload(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsBotPayload::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_description`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsDescription(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsDescription::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_extended_media`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsExtendedMedia(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsExtendedMedia::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_floodskip_number`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsFloodskipNumber(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsFloodskipNumber::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_giveaway_post_id`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsGiveawayPostId(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsGiveawayPostId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_msg_id`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsMsgId(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsMsgId::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_paid_messages`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsPaidMessages(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsPaidMessages::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_peer`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsPeer(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsPeer::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_photo`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsPhoto(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_premium_gift_months`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsPremiumGiftMonths(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsPremiumGiftMonths::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_stargift`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsStargift(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStargift::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_starref_amount`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsStarrefAmount(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStarrefAmount::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_starref_commission_permille`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsStarrefCommissionPermille(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStarrefCommissionPermille::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_starref_peer`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsStarrefPeer(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsStarrefPeer::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_subscription_period`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsSubscriptionPeriod(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsSubscriptionPeriod::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_title`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsTitle(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsTitle::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_transaction_date`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsTransactionDate(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsTransactionDate::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_transactions_transaction_url`(account_id, id) -> `tf_stars_transactions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsTransactionsTransactionUrl(): HasMany
    {
        return $this->hasMany(TfStarsTransactionsTransactionUrl::class, 'id', 'id');
    }
}
