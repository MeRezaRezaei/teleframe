<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Support\Facades\DB;

/**
 * TDLib-style TTL janitor: periodic expiry sweep that removes rows
 * whose TTL column has passed. Uses TDLib's double-limit pattern:
 * DELETE ... WHERE expires_at <= now() LIMIT batchSize, repeated
 * until a batch returns fewer than batchSize rows.
 *
 * Wired into the daemon's main loop or a scheduler:
 *   $janitor->sweep();
 */
final class TtlJanitor
{
    /** Default batch size (TDLib uses 101). */
    private const DEFAULT_BATCH = 101;

    /** Tables with TTL columns that the janitor sweeps. */
    private const TTL_TABLES = [
        'tf_stories' => 'expire_date',
    ];

    public function __construct(
        private readonly int $batchSize = self::DEFAULT_BATCH,
    ) {}

    /**
     * Sweep all configured TTL tables. Returns per-table deletion counts.
     *
     * @return list<array{table: string, deleted: int}>
     */
    public function sweep(): array
    {
        $results = [];
        $now = now();

        foreach (self::TTL_TABLES as $table => $ttlColumn) {
            $totalDeleted = 0;

            // TDLib double-limit pattern: keep deleting in batches until
            // a batch returns fewer than batchSize (meaning we're done).
            do {
                $deleted = DB::table($table)
                    ->where($ttlColumn, '<=', $now)
                    ->limit($this->batchSize)
                    ->delete();

                $totalDeleted += $deleted;
            } while ($deleted === $this->batchSize);

            if ($totalDeleted > 0) {
                $results[] = ['table' => $table, 'deleted' => $totalDeleted];
            }
        }

        return $results;
    }
}
