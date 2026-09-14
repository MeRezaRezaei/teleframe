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
 * - act_on (default): updates from this peer are stored AND emitted as
 *   app events (the second half of absolute truth);
 * - store_only: updates from this peer are persisted to the mirror DB but
 *   do NOT trigger events — the notify/log channel use case.
 *
 * The verbatim's "two Redis" topology: DB is source of truth, Redis holds
 * the hot-reloaded cache for fast per-update lookups. On DB change, an
 * observer copies the rule to Redis and fires an event so the running
 * daemon refreshes its in-memory routing table without restart.
 */
/**
 * @property int $account_id
 * @property int $peer_type
 * @property int $peer_id
 * @property string $mode
 * @property int $priority
 */
final class UpdateRoutingRule extends Model
{
    protected $table = 'tg_update_routing';

    /** @var bool telegram-reliance: IDs come from the app, not auto-increment */
    public $incrementing = false;

    protected $keyType = 'int';

    public const MODE_ACT_ON = 'act_on';

    public const MODE_STORE_ONLY = 'store_only';

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
