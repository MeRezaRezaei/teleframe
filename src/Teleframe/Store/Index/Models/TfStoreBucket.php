<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * tf_store_buckets row (verbatim S5): the local truth for a managed
 * channel — one row per {account, bucket} with the Telegram channel
 * identity and its kind (store/data/backup, from the title prefix).
 * Plain Eloquent model on the store truth tables; the store index layers
 * all querying behind StoreIndexContract (no global account scope here —
 * the index filters by account_id explicitly).
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, bucket) —
 * migration 2026_09_20_000100, $table->primary(['account_id', 'bucket']).
 * Eloquent has a single key slot, so account_id (the leading column, and
 * the tenant discriminator every store query already filters by) is the
 * key surrogate. Composite-key behaviour is inherently PARTIAL: getKey()
 * and find() are ambiguous (many buckets per account) — address a row by
 * its natural key, and HasStoreCompositeKey keeps save()/delete() honest.
 */
final class TfStoreBucket extends Model
{
    use HasStoreCompositeKey;

    protected $table = 'tf_store_buckets';

    /**
     * Eloquent key surrogate: the leading column of the real composite key.
     */
    protected $primaryKey = 'account_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'channel_id' => 'int',
        'access_hash' => 'int',
        'created_at_unix' => 'int',
    ];

    /**
     * {@inheritDoc}
     */
    protected function storeCompositeKeyColumns(): array
    {
        return ['account_id', 'bucket'];
    }
}
