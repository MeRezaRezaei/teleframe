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
        // Curated dial (2026_09_14_*) carries NO expiry-sweep surface: the
        // generated tf_stories table this janitor swept was purged with the
        // legacy mirror and no hand-authored table has a TTL column. The
        // sweep is deliberately empty until the curated dial gains one.
    ];

    /**
     * The configured TTL table → TTL-column map. Kept behind an accessor so
     * the sweep loop sees the contract type (array<string, string>) rather
     * than the currently-empty constant value; re-enable by adding entries
     * to {@see TTL_TABLES}.
     *
     * @return array<string, string>
     */
    protected static function ttlTables(): array
    {
        return self::TTL_TABLES;
    }

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

        foreach (self::ttlTables() as $table => $ttlColumn) {
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
