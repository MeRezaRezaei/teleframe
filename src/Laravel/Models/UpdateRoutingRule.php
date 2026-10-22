<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Per-peer update routing rule — the verbatim's loop-prevention settings layer.
 *
 * Owner verbatim 2026-09-14: "when i make a notify channel for one of the
 * apps i should not handle the updates of that channel as new event but
 * only update that only stores the final truth".
 *
 * Each row classifies one (account_id, peer_type, peer_id) triple as:
 * - act_on: updates from this peer are stored AND emitted as
 *   app events (the second half of absolute truth);
 * - store_only: updates from this peer are persisted to the mirror DB but
 *   do NOT trigger events — the notify/log channel use case.
 *
 * Neither mode is the row default in the schema sense that matters: the
 * COLUMN default is 'act_on', but UpdateRouter's own default for an UNMARKED
 * peer is store_only, so a peer with no row is never acted on. Opting in is
 * a write an operator must perform — `teleframe:routing <id> --account-wide
 * --mode=act_on` (or per-peer with --peer/--peer-id).
 *
 * The verbatim's "two Redis" topology: DB is source of truth, Redis holds
 * the hot-reloaded cache for fast per-update lookups. On DB change, an
 * observer copies the rule to Redis and fires an event so the running
 * daemon refreshes its in-memory routing table without restart.
 */
/**
 * @property int $id
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $mode
 * @property int $priority
 */
final class UpdateRoutingRule extends Model
{
    protected $table = 'tg_update_routing';

    /**
     * The schema's key IS the auto-increment `id` from `$table->id()` in
     * 2026_09_14_000001_create_tg_update_routing_table — an unsigned bigint
     * auto-increment. A model that claims $incrementing = false makes Eloquent
     * skip the last-insert-id read, so getKey() returned null after every
     * insert and any find($id) could never resolve. Routing never noticed
     * because every reader keys on the natural key (account_id, peer_type,
     * peer_id) — that stays the semantic identity, and updateOrCreate() on it
     * is unaffected by the primary key.
     */
    protected $keyType = 'int';

    public const MODE_ACT_ON = 'act_on';

    public const MODE_STORE_ONLY = 'store_only';

    /**
     * The account-level catch-all rule peer (peer_type=0, peer_id=0): the
     * default route the daemon pre-seeds for a discovered account. When no
     * rule marks a specific peer, UpdateRouter falls back to it, so an
     * operator flipping this row (store_only → act_on) changes the default
     * for the WHOLE account in real time — Teleframe::ignore() with no peer
     * args writes the same pair.
     */
    public const DEFAULT_PEER_TYPE = 0;

    public const DEFAULT_PEER_ID = 0;

    /** @var list<string> */
    protected $fillable = [
        'account_id',
        'peer_type',
        'peer_id',
        'mode',
        'priority',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'account_id' => 'int',
        'peer_type' => 'int',
        'peer_id' => 'int',
        'priority' => 'int',
    ];
}
