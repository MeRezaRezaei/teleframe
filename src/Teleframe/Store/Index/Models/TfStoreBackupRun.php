<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Store\Index\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * tf_store_backup_runs row (remainder design §6): the run ledger — one row
 * per {account, object_name} with the dump's identity, the stored object's
 * observed truth (bytes/chunks/sha256/mode), status (ok|queued), and the
 * run heartbeat (heartbeat_at_unix, null until the upload is observed).
 * peer_type is the wave-2 surfacing slot (channel|group target). Real
 * autoincrement id + timestamps (unlike the composite-key store tables):
 * this ledger is an append-only history, not a state projection.
 *
 * Provenance: docs/superpowers/plans/2026-09-24-backup-store-remainder-design.md
 * + migration 2026_09_24_000100_create_tf_store_backup_runs_table.
 */
final class TfStoreBackupRun extends Model
{
    protected $table = 'tf_store_backup_runs';

    public $timestamps = true;

    protected $fillable = [
        'account_id',
        'bucket',
        'peer_type',
        'driver',
        'database',
        'object_name',
        'bytes',
        'chunks',
        'sha256',
        'mode',
        'status',
        'heartbeat_at_unix',
    ];

    protected $casts = [
        'bytes' => 'int',
        'chunks' => 'int',
        'heartbeat_at_unix' => 'int',
    ];
}
