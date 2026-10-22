<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MeRezaRezaei\Teleframe\Mirror\Models\ResolvesEntityPeer;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_stars_subscriptions`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator.
 *
 * A relation appears here in one of two shapes, and the docblock on each says
 * which: one where the engine DECLARES a foreign key, and one HORIZONTAL — an
 * entity-to-entity edge derived from an inline `{base}_type` + `{base}_id` peer
 * pair or from a catalog-declared scalar entity column, which no constraint can
 * express. See HorizontalEntityRelations for why those are not FKs.
 *
 * Relations declared on this table: 6.
 */
final class TfStarsSubscriptions extends TfMirrorModel
{
    use ResolvesEntityPeer;

    use AccountScoped;

    protected $table = 'tf_stars_subscriptions';

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
        'peer_type' => 'int',
        'peer_id' => 'int',
        'until_date' => 'int',
        'canceled' => 'boolean',
        'can_refulfill' => 'boolean',
        'missing_balance' => 'boolean',
        'bot_canceled' => 'boolean',
    ];

    /** HORIZONTAL, not a foreign key. `tf_stars_subscriptions`.`peer_type` is a `tinyint` discriminator and `tf_stars_subscriptions`.`peer_id` the Telegram peer id beside it, and one FK cannot branch on a type int (2026_09_14_299999:25-31). Resolves against `tf_users`, `tf_chats` or `tf_channels` by the discriminator, pinned to this account. Null when the discriminator is 0 — Telegram did not send a peer — and RAISES on a discriminator outside the enum rather than returning an empty relation, which is what a wrong key produces. */
    public function peer(): ?BelongsTo
    {
        return $this->entityPeer('peer_type', 'peer_id');
    }

    /** Declared foreign key (1:N): `tf_stars_subscriptions_chat_invite_hash`(account_id, id) -> `tf_stars_subscriptions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsSubscriptionsChatInviteHash(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptionsChatInviteHash::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_subscriptions_invoice_slug`(account_id, id) -> `tf_stars_subscriptions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsSubscriptionsInvoiceSlug(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptionsInvoiceSlug::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_subscriptions_photo`(account_id, id) -> `tf_stars_subscriptions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsSubscriptionsPhoto(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptionsPhoto::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_subscriptions_pricing`(account_id, id) -> `tf_stars_subscriptions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsSubscriptionsPricing(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptionsPricing::class, 'id', 'id');
    }

    /** Declared foreign key (1:N): `tf_stars_subscriptions_title`(account_id, id) -> `tf_stars_subscriptions`(account_id, id), ON DELETE CASCADE. Keyed on `id` = `id`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function starsSubscriptionsTitle(): HasMany
    {
        return $this->hasMany(TfStarsSubscriptionsTitle::class, 'id', 'id');
    }
}
