<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Console;

use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\ChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\MirrorChunkSync;
use MeRezaRezaei\Teleframe\Ingest\MtprotoChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use MeRezaRezaei\Teleframe\Laravel\Models\TfIngestClue;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Laravel\Services\TeleframeClient;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorChunkDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;

/**
 * artisan teleframe:ingest:gate — the session-register schema-integrity gate.
 *
 * Owner verbatim 2026-09-14: "at session register we are going to ask
 * teelgram to send all updates as a chunck and if we can ingest the big
 * update the full shcema is correct this is the way i can make sure my app
 * is working".
 *
 * Runs the mirror chunk sync for one account (updates.getState →
 * updates.getDifference → decompose → write) and reports integrity:
 * ZERO CLUES across any of the four buckets the mirror computes — the
 * decomposer's own coverage clues, fkClues, unmappedClues, valueClues — means
 * the full schema is correct for that data. Every clue is persisted to
 * `tf_ingest_clues` (per account, per bucket, with the table parsed off the
 * clue's `{$table}: …` prefix), so the accounting outlives the scrollback.
 *
 * A failing chunk does NOT advance the pts watermark — with ONE deliberate
 * exception, `unmappedClues` alone. The cursors are monotonic, so advancing
 * over data the run failed to place pushes it outside the next resync window,
 * where nothing ever revisits it. But an unmodelled TL field is a gap in the
 * curated dial that no retry closes, so holding the cursor there would re-fetch
 * the same chunk forever and strand every chunk behind it. Failing is still
 * unconditional; only the HOLD is scoped. See the verdict below.
 *
 * The wire half is a container seam (mirrors BackfillCommand): FETCHER_KEY
 * binds callable(int $accountId): ChunkFetcher. The default builds a live
 * MtprotoChunkFetcher over a TeleframeClient::user() scope; hosts that test
 * the gate offline rebind the key to a fixture fetcher.
 */
final class IngestGateCommand extends Command
{
    /** Container seam: callable(int $accountId): ChunkFetcher — string literal on purpose (host-rebindable). */
    public const FETCHER_KEY = 'teleclient.ingest-gate.fetcher';

    protected $signature = 'teleframe:ingest:gate
        {account : Telegram account id to gate against}
        {--only : Only verify integrity; do NOT persist the new watermark}';

    protected $description = 'Session-register gate: ingest the Telegram big update chunk as the schema-correctness proof';

    protected $aliases = [];

    public function handle(): int
    {
        $accountId = (int) $this->argument('account');
        if ($accountId <= 0) {
            $this->error('account must be a positive integer.');

            return self::FAILURE;
        }

        $fetcher = $this->resolveFetcher()($accountId);

        // Mirror pipeline is pure (no host wiring) — construct from the
        // committed schema artifacts, exactly like teleframe:mirror does.
        $tl = SchemaArtifacts::tl();
        $catalogPath = SchemaArtifacts::catalog();
        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $sync = new MirrorChunkSync(
            new MirrorChunkDecomposer(
                new MirrorFactDecomposer($resolver, $catalog),
                $catalog,
            ),
            new MirrorFactWriter,
        );

        $watermark = $this->getLaravel()->make(PtsWatermark::class);
        $prior = $watermark->get($accountId);

        try {
            $report = $sync->sync(DB::connection(), $accountId, $fetcher, $prior);
        } catch (\Throwable $e) {
            $this->error("Chunk sync failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        // The ledger is written BEFORE the verdict, and independently of it: a
        // gate that prints "Integrity OK" must still have recorded what it could
        // not place, and a gate that fails must have left the evidence behind.
        // Recording is also outside the sync transaction, so a rolled-back
        // mirror write still leaves the clue that says why.
        $connection = DB::connection();
        TfIngestClue::recordReport(
            $connection,
            $report,
            $accountId,
            CarbonImmutable::createFromTimestampUTC($report['state']['date']),
        );

        $rows = [
            ['account', $accountId],
            ['prior pts', $prior['pts'] ?? '—'],
            ['new pts', $report['state']['pts']],
            ['rows', $report['rows']],
            // LABEL CORRECTED 2026-10-04: this row used to read `inserted`, which
            // told an operator reading a gate verdict that N rows are in the
            // mirror. It is N STATEMENTS PLACED, and the two differ whenever two
            // facts collide on one primary key — measured on the shipped DDL,
            // three facts sharing a key print 3 here and the table holds one row,
            // with every clue bucket empty. On a gate whose whole purpose is
            // answering "does the mirror hold this chunk", the counter's own name
            // is the one place the difference would be read, so the label names
            // what it counts. The number itself is unchanged, and nothing gates
            // or advances on it — the verdict below reads the four clue buckets.
            // `rows` above it is what the decomposer OFFERED, so the pair is
            // "offered / reached"; EQUAL values do not prove the mirror holds that
            // many rows, and only the verdict gates.
            ['statements placed', $report['inserted']],
            ['coverage clues', count($report['clues'])],
            ['fk clues', count($report['fkClues'])],
            ['unmapped fields', count($report['unmappedClues'])],
        ];

        // The value bucket is APPENDED ONLY WHEN NON-EMPTY, deliberately. A
        // healthy run's output must stay byte-for-byte what it has always
        // been, and an unconditional `value clues: 0` row would change every
        // clean run forever to say nothing. The presence of the row IS the
        // signal. It now ALSO gates (see the verdict below): a value fault was
        // always a real fault, and "reported but forgiven" is the same defect
        // shape as the coverage bucket's — printed, then ignored.
        if ($report['valueClues'] !== []) {
            $rows[] = ['value clues', count($report['valueClues'])];
        }

        $this->table(['fact', 'value'], $rows);

        foreach ($report['clues'] as $clue) {
            // The coverage-gap list: a fact the mirror could not place because
            // the schema does not describe it. Named apart from the FK bucket
            // because it IS the gap list — until 2026-10-02 this bucket was
            // printed here and then ignored by the exit condition below, while
            // the run announced "Integrity OK".
            $this->warn("  coverage clue: {$clue}");
        }
        foreach ($report['fkClues'] as $clue) {
            $this->warn("  FK clue: {$clue}");
        }
        foreach ($report['unmappedClues'] as $clue) {
            // The shipped DDL does not model this TL field — a modelling gap in
            // the schema, and NORMAL for a mirror: the wire runs AHEAD of the
            // curated dial, so a payload legitimately carrying a field the DDL
            // has never heard of is the expected bucket on live traffic, not a
            // transient fault.
            //
            // ITS ROLE CHANGED ON 2026-10-02, and this is the comment an
            // operator stops at, so it says so. It used to read "Reported,
            // never a failure" — the bucket was printed and then forgiven while
            // the run announced "Integrity OK". It now FAILS the run, because a
            // modelling gap the gate forgives is a hole nothing else will ever
            // notice: the field is dropped on the floor and the exit code is the
            // only thing that would have said so. Failing is the honest verdict.
            // What that does NOT do is hold the watermark — see the advance
            // condition below, because no retry fixes a gap in the dial.
            //
            // Still kept distinct from the FK bucket (a relationship problem) so
            // a reader is not sent to the wrong graph.
            $this->warn("  unmapped field: {$clue}");
        }
        foreach ($report['valueClues'] as $clue) {
            // Its own label, never folded into the FK list: a value fault means
            // the VALUE does not fit the column the DDL gives it (MySQL
            // 1265/1364/1366/1406, Postgres 22001) — a column-shape problem.
            // Filing it as "FK constraint failed" sends the next reader to the
            // relationship graph instead of to the column.
            $this->warn("  value clue: {$clue}");
        }

        // ALL FOUR BUCKETS GATE, not just fkClues. Measured 2026-10-02: the
        // condition was `fkClues !== []`, so the decomposer's own coverage
        // clues — the very list that says which parts of Telegram's schema this
        // mirror still does not model — printed as warnings and then exited 0
        // under "Integrity OK". Each bucket is a distinct axis, so the verdict
        // names every axis that fired instead of a single one.
        $failed = [];
        foreach (TfIngestClue::buckets() as $bucket) {
            if (($report[$bucket] ?? []) !== []) {
                $failed[] = $bucket;
            }
        }

        // FAILING AND HOLDING ARE TWO DIFFERENT DECISIONS, and as of 2026-10-02
        // they are made in two different places. Failing is unconditional: all
        // four buckets stay in the exit condition above. HOLDING the watermark
        // is scoped to the buckets a RETRY can actually fix.
        //
        // WHY. The cursors are monotonic, so holding one over data that cannot
        // become placeable re-fetches the same chunk forever: an unmodelled TL
        // field is a gap in the curated dial, and the dial does not change
        // between two runs of this command. Holding there buys a re-fetch loop
        // and costs the account every chunk behind it. The other three buckets
        // are transient by nature — a missing foreign key row, a truncating
        // column, a constructor the catalog has not learned yet — and a later
        // run over the same chunk can genuinely place them.
        $holds = $failed !== [] && $failed !== ['unmappedClues'];

        if ($failed !== []) {
            if ($holds) {
                $this->error('Schema integrity NOT reached — '.implode(', ', $failed)
                    .' recorded in tf_ingest_clues; the watermark is NOT advanced.');

                return self::FAILURE;
            }

            // unmappedClues ALONE: a modelling gap in the curated dial, not a
            // fault this run can retry. The run still FAILS — the bucket is
            // real and the operator still has to model the field — but the
            // cursor moves, because holding it would strand every chunk behind
            // this one on a gap no amount of retrying will close.
            $this->error('Schema integrity NOT reached — unmappedClues'
                .' recorded in tf_ingest_clues; unmapped fields are a modelling gap;'
                .' watermark advanced so the account is not stuck re-fetching this chunk.');

            if (! $this->option('only')) {
                $watermark->put($accountId, $report['state']);
            }

            return self::FAILURE;
        }

        // THE WATERMARK IS ADVANCED ONLY AFTER THE VERDICT. It used to be
        // persisted at the top of handle(), BEFORE the clue check, so a failing
        // run moved the cursor over the very chunk it had just failed to place.
        // The cursors are monotonic, so that data fell outside the next resync
        // window and was never revisited — a coverage gap became a silent,
        // permanent hole.
        if (! $this->option('only')) {
            $watermark->put($accountId, $report['state']);
        }

        $this->info($this->option('only')
            ? 'Integrity OK — full schema is correct for this chunk (watermark not persisted).'
            : 'Integrity OK — full schema is correct for this chunk; watermark persisted.');

        return self::SUCCESS;
    }

    private function resolveFetcher(): callable
    {
        $app = $this->getLaravel();

        if ($app->bound(self::FETCHER_KEY)) {
            return $app->make(self::FETCHER_KEY);
        }

        return static function (int $accountId) use ($app): ChunkFetcher {
            $client = $app->make(TeleframeClient::class);

            return new MtprotoChunkFetcher($client->user($accountId)->mtproto);
        };
    }
}
