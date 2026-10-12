<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Mirror\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * tf_backfill_cursors — the durable per-peer backfill progress LEDGER that
 * lets the crawl REACH message id 1: one row per {account_id, peer_key}
 * recording how far that dialog's history crawl has walked (offset_id), how
 * many runs it has failed (attempts), and where it ended (status
 * active|complete|dead, with the dead-letter reason).
 *
 * This is the ONE table in this family that is a real autoincrement ledger
 * rather than a timestamp-free state projection, which is why both inherited
 * flags are overridden back on below. It is deliberately a ledger and not a
 * projection because a peer's crawl history must stay auditable across
 * reclassification; peer_key is a dialog KEY, not a chat id, so a key that no
 * longer resolves into an inputPeer keeps its progress instead of silently
 * restarting at 0.
 *
 * Deliberately NO FK to any bucket or dialog: a cursor is a ledger fact about
 * an account and it outlives dialog reclassification
 * (2026_09_25_000300:22-23). The (account_id, peer_key) pair is UNIQUE, so a
 * peer's progress is single-valued and `updateOrCreate` is the write shape.
 *
 * `offset_id` is UNSIGNED BIG on purpose: Telegram message ids are 32-bit and
 * only grow, so a 64-bit column never wraps in a long-lived deployment.
 *
 * @property int $id
 * @property int $account_id
 * @property string $peer_key
 * @property int $offset_id
 * @property int $attempts
 * @property string $status
 * @property string|null $reason
 * @property int|null $last_offset_at_unix
 */
final class TfBackfillCursor extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_backfill_cursors';

    /**
     * The DDL declares a REAL autoincrement primary key via $table->id()
     * (2026_09_25_000300:37), so `id` is a genuine surrogate and
     * TfMirrorModel's inherited $primaryKey = 'id' is already correct here.
     */
    protected $primaryKey = 'id';

    /**
     * Overridden back to TRUE against TfMirrorModel's inherited `false`
     * ($incrementing): this table DOES autoincrement, and leaving it false
     * would make Eloquent skip the post-insert lastInsertId() read and hand
     * back a model whose id disagrees with its own DB row.
     */
    public $incrementing = true;

    protected $keyType = 'int';

    /**
     * Overridden back to TRUE against TfMirrorModel's inherited `false`: the
     * DDL calls $table->timestamps() (2026_09_25_000300:45). This is the
     * exception to the curated dial's timestamp-free rule and it is deliberate
     * — a cursor row carries a heartbeat (last_offset_at_unix) that only
     * means something next to its own created_at/updated_at.
     */
    public $timestamps = true;

    protected $guarded = [];

    protected $casts = [
        'id' => 'integer',
        'account_id' => 'integer',
        'offset_id' => 'integer',
        'attempts' => 'integer',
        'last_offset_at_unix' => 'integer',
    ];
}
