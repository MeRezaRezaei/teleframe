<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * tf_store_media row (verbatim S3): the media hash index — one row per
 * indexed media message in a data channel ({account, bucket, msg_id}),
 * carrying the Telegram-native document identity (doc_id/access_hash/
 * file_reference) plus our content sha256. content_sha256 is null until
 * the bytes pass through the cache (lazy hash, S3 spec).
 *
 * KEY: the DDL primary key is COMPOSITE (account_id, bucket, msg_id) —
 * migration 2026_09_20_000100, $table->primary(['account_id','bucket','msg_id']).
 * Eloquent has a single key slot, so account_id (the leading column, and
 * the tenant discriminator every store query already filters by) is the
 * key surrogate. Composite-key behaviour is inherently PARTIAL: getKey()
 * and find() are ambiguous (many media rows per account) — address a row
 * by its natural key, and HasStoreCompositeKey keeps save()/delete() honest.
 */
final class TfStoreMedia extends Model
{
    use HasStoreCompositeKey;

    protected $table = 'tf_store_media';

    /**
     * Eloquent key surrogate: the leading column of the real composite key.
     */
    protected $primaryKey = 'account_id';

    protected $keyType = 'int';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'msg_id' => 'int',
        'doc_id' => 'int',
        'access_hash' => 'int',
        'size' => 'int',
        'indexed_at_unix' => 'int',
    ];

    /**
     * {@inheritDoc}
     */
    protected function storeCompositeKeyColumns(): array
    {
        return ['account_id', 'bucket', 'msg_id'];
    }
}
