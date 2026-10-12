<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Laravel\Models\TfIngestClue;
use Throwable;

/**
 * artisan teleframe:clues — READ the ingest clue ledger, aggregated.
 *
 * Owner verbatim 2026-10-05: "it can be used as a SIGN OF FAILURE since if we
 * have added all telegram history whatever new update has the previous related
 * data" and "the INTEGRITY OF DATA IN THE DATABASE means everything".
 *
 * ## WHAT THIS IS, AND WHAT IT IS NOT
 *
 * The mirror already computes four clue buckets per fact — `clues`, `fkClues`,
 * `unmappedClues`, `valueClues` — and three surfaces already PERSIST them, one
 * row per clue string, into `tf_ingest_clues` through one shared entry point
 * (`TfIngestClue::recordReport()`, `src/Laravel/Models/TfIngestClue.php:112`):
 * the bus path (`MirrorIngesterFactory::recordClues()`), the backfill
 * (`BackfillCommand::recordClues()`) and the ingest gate
 * (`IngestGateCommand::handle()`).
 *
 * **WHAT DID NOT EXIST IS A READER.** Measured 2026-10-05 rather than assumed:
 * `grep -rn "tf_ingest_clues" src/ --include=*.php` filtered for
 * select/count/where/orderBy/get returns ZERO hits, and `TfIngestClue` appears
 * in `src/` only at `recordReport()` (the write) and `buckets()` (the fold). No
 * command, no controller, no view, no dashboard. The repository says so itself
 * at `src/Schema/Mirror/MirrorFactWriter.php:208`: "NOBODY FILTERS ON THE
 * BUCKET … read by no controller, view, query or dashboard in the repository".
 * So the operator's failure signal — a fact that should have been placed and was
 * not — was written down and then never shown to anybody. **A row that nothing
 * reads is not a signal; it is a receipt.**
 *
 * ## WHY THIS IS A READ COMMAND AND NOT A LOG LINE OR AN EVENT
 *
 * The alternatives were measured against this package's own constraints rather
 * than chosen by taste:
 *
 *   - **A LOG LINE cannot be the answer.** PSR-3 is the seam, and
 *     `TeleframeServiceProvider` resolves an unconfigured host to `NullLogger`,
 *     so the engine's silence-by-default is a PROMISED property of this package
 *     (`AGENTS.md`: "byte-for-byte silent when unconfigured"). Making the failure
 *     loud by default would break that promise, and making it loud on demand
 *     still leaves a line that dies with the process and cannot answer "how many
 *     facts did this mirror lose last Tuesday".
 *   - **AN EVENT cannot be the answer.** `src/Laravel/Events/` is the right
 *     convention for telling a host that ALREADY wired a listener
 *     (`TelegramGapDetected` is dispatched from `UpdatePollerService`). With no
 *     listener — the shipped default — an event is exactly today's silence, and
 *     it would fire once per clue on the highest-volume path in the repository.
 *   - **A SECOND TABLE is not needed.** `tf_ingest_clues` already is the ledger,
 *     is written from all three recording surfaces, and is already indexed for
 *     precisely this query (`ix_tf_ingest_clues_account_bucket`,
 *     `ix_tf_ingest_clues_account_seen`). Adding a table beside it would be the
 *     bespoke mechanism that must not be invented when a convention already fits.
 *
 * ## WHY IT AGGREGATES, AND WHY THAT IS THE ANTI-STORM ANSWER
 *
 * The ledger is APPEND-ONLY and never deduplicated — `TfIngestClue` declares
 * `$incrementing = false` and `$primaryKey = null`, and `recordReport()` inserts
 * ONE ROW PER CLUE STRING. The operator's own criterion is a full-history
 * backfill followed by continuous updates, and the backfill is the
 * highest-volume writer in the repository (`BackfillCommand`'s own docblock
 * calls it that). A backfill that loses the same fact on 400 pages files 400
 * indistinguishable rows, with nothing to say whether the fault is STILL
 * HAPPENING or was repaired three weeks ago.
 *
 * So this command aggregates IN SQL — `GROUP BY bucket, table` with `count(*)`
 * and `count(DISTINCT detail)` — and prints two numbers for every group:
 *
 *   `rows`   how many ledger rows exist. VOLUME.
 *   `faults` how many DISTINCT clue strings produced them. THE NUMBER TO ACT ON.
 *
 * Both are printed because reading either alone is wrong: `rows` alone looks
 * like 400 separate problems, `faults` alone hides a fault that is firing on
 * every single page. Aggregation is also what makes the output BOUNDED — the
 * result is capped by distinct `(bucket, table)` pairs, not by the number of
 * clues — which is why this is safe at backfill volume where a per-clue log line
 * would be a storm of its own.
 *
 * The row cap is `--details` (default 3 per group), so `--details=0` prints the
 * counts alone and stays cheap on a ledger with millions of rows.
 *
 * ### WHAT `faults` IS NOT, MEASURED 2026-10-05
 *
 * `faults` is `COUNT(DISTINCT detail)`, and `detail` is the clue STRING the
 * writer built — which embeds the driver's entire message, statement included.
 * So two occurrences of ONE root cause on two different rows are two distinct
 * strings and read as two faults. Measured: a batch hitting an orphan
 * `tf_messages_media` on four pages and a second orphan on a fifth files
 * **5 rows and 2 fkClues faults**, where a human reads the two example lines and
 * sees one class of problem.
 *
 * That is a deliberate over-count rather than an under-count, and the reason is
 * that collapsing them is not this command's to do: the grouping key that would
 * do it — the arm's own `"{$table}: <CLASS> — "` prefix — is
 * `MirrorFactWriter`'s vocabulary, and that class is explicitly not to be
 * re-derived here. A number that quietly merged two axes would send a reader to
 * the wrong graph, which is the exact lie the four buckets exist to prevent.
 * `rows` never over-states, `faults` never under-states, and `--details` shows
 * the strings so the collapse is one glance.
 *
 * ## THE EXIT CODE IS THE POINT, because it is what makes this ACTIONABLE
 *
 * `teleframe:ingest:gate` already sets the convention this follows: a non-empty
 * bucket is a FAILURE, not a warning (`IngestGateCommand.php:201-245`, which
 * returns `self::FAILURE` on every non-empty bucket). So this command returns
 * `self::FAILURE` when anything is recorded and `self::SUCCESS` when nothing is,
 * which means an operator can wire it into cron, a deploy gate or a health check
 * and have a broken mirror announce itself without a human reading scrollback.
 *
 * ## IT WRITES NOTHING AND DIALS NOTHING
 *
 * Read-only, and it makes no network call, so running it cannot change mirror
 * state and cannot contact Telegram. A host that has not migrated
 * `tf_ingest_clues` is told so and told how to fix it, rather than handed a
 * stack trace — the same non-fatal contract `MirrorIngesterFactory::recordClues()`
 * and `BackfillCommand::recordClues()` both keep for the same table.
 */
final class CluesCommand extends Command
{
    protected $signature = 'teleframe:clues
        {account? : Limit to one Telegram account id (tenant)}
        {--since= : Only clues seen on or after this date, Y-m-d}
        {--details=3 : Example clue strings to print per group; 0 prints counts alone}';

    protected $description = 'Report what the mirror FAILED to store, aggregated per bucket and table (exit 1 when anything was lost)';

    protected $aliases = ['teleframe:ingest:clues'];

    public function handle(): int
    {
        $account = $this->argument('account');

        if ($account !== null && (int) $account <= 0) {
            $this->error('account must be a positive integer.');

            return self::FAILURE;
        }

        $since = $this->since();

        if ($since === false) {
            $this->error('--since must be a date this engine can read, as Y-m-d.');

            return self::FAILURE;
        }

        $details = max(0, (int) $this->option('details'));

        try {
            // Every predicate is applied in ONE query, server-side. The whole
            // point of this command is that it stays cheap when the ledger holds
            // the volume a full-history backfill produces, so it must never
            // select the rows and aggregate them in PHP.
            $groups = $this->groups($account === null ? null : (int) $account, $since);
            $rows = $this->count($account === null ? null : (int) $account, $since);
        } catch (QueryException $e) {
            // Non-fatal and SPECIFIC, because the overwhelmingly likely cause is
            // a host that has not run the ledger migration, and a raw
            // "no such table" tells that host nothing about what to do. The two
            // other recording surfaces already swallow this same table's absence
            // for the same reason (`MirrorIngesterFactory.php:493`,
            // `BackfillCommand.php:944`).
            $this->error('the ingest clue ledger could not be read: '.$e->getMessage());
            $this->line('run `php artisan migrate` if this host has never migrated tf_ingest_clues.');

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('the ingest clue ledger could not be read: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($rows === 0) {
            // The control half. A signal that is always on is not a signal, so
            // the clean answer is stated in the same words on every run and the
            // command SUCCEEDS.
            $this->info(sprintf(
                'account %s · tf_ingest_clues · no clue recorded — every fact this mirror tried to place, it placed.',
                $account === null ? 'all' : (string) $account,
            ));

            return self::SUCCESS;
        }

        $this->table(
            ['bucket', 'table', 'rows', 'faults', 'first seen', 'last seen'],
            array_map(
                static fn (object $group): array => [
                    $group->bucket,
                    $group->table,
                    (string) (int) $group->rows,
                    (string) (int) $group->faults,
                    (string) $group->first_seen,
                    (string) $group->last_seen,
                ],
                $groups,
            ),
        );

        if ($details > 0) {
            // Printed UNDER its own bucket and table, never merged: the buckets
            // are three genuinely different axes and the file that produces them
            // says so at length (`MirrorFactWriter`, the three-bucket comment).
            // Filing a value fault under the relationship axis sends the next
            // reader to the relationship graph instead of to the column.
            foreach ($groups as $group) {
                foreach ($this->examples($group->bucket, $group->table, $account === null ? null : (int) $account, $since, $details) as $detail) {
                    $this->warn(sprintf('  %s / %s: %s', $group->bucket, $group->table, $detail));
                }
            }
        }

        $faults = 0;
        foreach ($groups as $group) {
            $faults += (int) $group->faults;
        }

        $this->error(sprintf(
            '%d fault%s across %d bucket%s from %d ledger row%s — facts the mirror did NOT store.'
            .' Rows are volume; distinct faults are what to fix.',
            $faults,
            $faults === 1 ? '' : 's',
            count($groups),
            count($groups) === 1 ? '' : 's',
            $rows,
            $rows === 1 ? '' : 's',
        ));

        return self::FAILURE;
    }

    /**
     * One row per `(bucket, table)` pair: how many ledger rows exist, how many
     * DISTINCT clue strings produced them, and the window they were seen in.
     *
     * `count(DISTINCT detail)` is the actionable figure and the reason this
     * command exists rather than a `select *`: the ledger never deduplicates, so
     * the raw row count is a measure of how many times a run hit a fault, not of
     * how many faults there are. The window is what says whether one is STILL
     * HAPPENING — a fault whose `last_seen` is old has been quiet since, and
     * that is a different piece of news from one seen a minute ago.
     *
     * @return list<object>
     */
    private function groups(?int $account, ?string $since): array
    {
        $query = DB::connection()->table((new TfIngestClue)->getTable())
            ->select('bucket', 'table')
            ->selectRaw('COUNT(*) AS rows')
            ->selectRaw('COUNT(DISTINCT detail) AS faults')
            ->selectRaw('MIN(seen_at) AS first_seen')
            ->selectRaw('MAX(seen_at) AS last_seen')
            ->groupBy('bucket', 'table')
            ->orderByDesc('rows');

        return $this->scoped($query, $account, $since)->get()->all();
    }

    /**
     * The total number of ledger rows in scope — the VOLUME figure, printed
     * beside the fault count so neither can be misread as the other.
     */
    private function count(?int $account, ?string $since): int
    {
        $query = DB::connection()->table((new TfIngestClue)->getTable());

        return (int) $this->scoped($query, $account, $since)->count();
    }

    /**
     * Up to `$limit` DISTINCT clue strings for one group.
     *
     * `distinct()` because the point of the group is that its rows repeat: three
     * examples of the same string teach nothing that one does not, and printing
     * the repeat is what makes a raw ledger dump unreadable in the first place.
     *
     * @return list<string>
     */
    private function examples(string $bucket, string $table, ?int $account, ?string $since, int $limit): array
    {
        $query = DB::connection()->table((new TfIngestClue)->getTable())
            ->where('bucket', $bucket)
            ->where('table', $table)
            ->distinct()
            ->orderBy('detail')
            ->limit($limit);

        return array_map(
            static fn (object $row): string => (string) $row->detail,
            $this->scoped($query, $account, $since)->get()->all(),
        );
    }

    /**
     * The account and window predicates, applied to every query this command
     * makes so the three can never disagree about scope.
     *
     * `seen_at` is monotonic with the ingest watermark by the migration's own
     * promise, so a window is a sound thing to filter on and `--since` can never
     * hide a clue the run it belongs to could still be asked about.
     *
     * @param  Builder  $query
     * @return Builder
     */
    private function scoped($query, ?int $account, ?string $since)
    {
        if ($account !== null) {
            $query->where('account_id', $account);
        }

        if ($since !== null) {
            $query->where('seen_at', '>=', $since);
        }

        return $query;
    }

    /**
     * `--since` as a `Y-m-d` day, or `false` when the argument is unreadable.
     *
     * `false` rather than null, because null is the legitimate "no window" value
     * and conflating the two would silently widen a scoped report back to all
     * time — the worst possible failure for a command whose whole job is to say
     * what was lost.
     */
    private function since(): string|false|null
    {
        $value = $this->option('since');

        if ($value === null || $value === '') {
            return null;
        }

        $parsed = date_create_immutable((string) $value, new \DateTimeZone('UTC'));

        return $parsed === false ? false : $parsed->format('Y-m-d 00:00:00');
    }
}
