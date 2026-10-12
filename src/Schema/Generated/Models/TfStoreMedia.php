<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_store_media`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 *
 * Relations declared on this table: 1.
 */
final class TfStoreMedia extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_store_media';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    /**
     * Eloquent gets ONE scalar key. The real key is the composite (account_id, bucket, msg_id),
     * which the database enforces; `msg_id` is total only WITHIN an account, and
     * AccountScope guarantees that. So `find()` is account-scoped by construction:
     * reach for a relation or an explicit where, never a bare find().
     */
    protected $primaryKey = 'msg_id';

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
        'bucket' => 'string',
        'msg_id' => 'int',
        'media_type' => 'string',
        'doc_id' => 'int',
        'access_hash' => 'int',
        'file_reference' => 'string',
        'size' => 'int',
        'mime' => 'string',
        'content_sha256' => 'string',
        'indexed_at_unix' => 'int',
    ];

    /** Declared foreign key: `tf_store_media`(account_id, bucket) -> `tf_store_buckets`(account_id, bucket). Keyed on `bucket` = `bucket`; the `account_id` leg is enforced by AccountScope and the full constraint by the database. */
    public function storeBuckets(): BelongsTo
    {
        return $this->belongsTo(TfStoreBuckets::class, 'bucket', 'bucket');
    }
}
