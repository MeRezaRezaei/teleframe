<?php

// GENERATED — do not edit; run artisan teleframe:regenerate

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Generated\Models;

use MeRezaRezaei\Teleframe\Schema\Eloquent\AccountScoped;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * Eloquent model for the shipped mirror table `tf_store_backup_runs`.
 *
 * Columns, casts and relations are REFLECTED from the executed shipped DDL, not
 * transcribed and not inferred from names — see ModelGenerator. A relation
 * appears here only where the engine DECLARES a foreign key.
 */
final class TfStoreBackupRuns extends TfMirrorModel
{
    use AccountScoped;

    protected $table = 'tf_store_backup_runs';

    protected $guarded = [];

    /** No autoincrement anywhere on the mirror: keys are Telegram-native. */
    public $incrementing = false;

    protected $primaryKey = 'id';

    public $timestamps = true;

    /**
     * Derived from the type the ENGINE reports for each column — never from a
     * docblock, and never from SQLite, whose affinity collapses integer(),
     * bigInteger() and tinyInteger() alike and drops width and signedness.
     *
     * @var array<string,string>
     */
    protected $casts = [
        'id' => 'int',
        'account_id' => 'int',
        'bucket' => 'string',
        'peer_type' => 'string',
        'driver' => 'string',
        'database' => 'string',
        'object_name' => 'string',
        'bytes' => 'int',
        'chunks' => 'int',
        'sha256' => 'string',
        'mode' => 'string',
        'status' => 'string',
        'heartbeat_at_unix' => 'int',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
