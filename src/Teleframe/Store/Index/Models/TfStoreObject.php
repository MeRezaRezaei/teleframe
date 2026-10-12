<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * tf_store_objects row (verbatim S2/S4): the object index — bucket + name
 * as the natural key, sealed/raw mode, byte size, the chunk plan
 * (ChunkPlanner rows as JSON: n/offset/size/per-chunk sha256) and the
 * TFSTORE1 metadata payload that was posted alongside. chunks/metadata
 * are cast to arrays (the DB columns are JSON text).
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, bucket, name) —
 * migration 2026_09_20_000100, $table->primary(['account_id','bucket','name']).
 * Eloquent has a single key slot, so account_id (the leading column, and
 * the tenant discriminator every store query already filters by) is the
 * key surrogate. Composite-key behaviour is inherently PARTIAL: getKey()
 * and find() are ambiguous (many objects per account) — address a row by
 * its natural key, and HasStoreCompositeKey keeps save()/delete() honest.
 */
final class TfStoreObject extends Model
{
    use HasStoreCompositeKey;

    protected $table = 'tf_store_objects';

    /**
     * Eloquent key surrogate: the leading column of the real composite key.
     */
    protected $primaryKey = 'account_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'size' => 'int',
        'chunks' => 'array',
        'metadata' => 'array',
        'created_at_unix' => 'int',
        'updated_at_unix' => 'int',
    ];

    /**
     * {@inheritDoc}
     */
    protected function storeCompositeKeyColumns(): array
    {
        return ['account_id', 'bucket', 'name'];
    }
}
