<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Console;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\ChunkFetcher;
use MeRezaRezaei\Teleframe\Ingest\PtsWatermark;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Laravel\Console\IngestGateCommand;
use MeRezaRezaei\Teleframe\Laravel\Providers\TeleframeServiceProvider;
use MeRezaRezaei\Teleframe\Tests\Support\ArrayRedis;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

/**
 * A coverage gap must be RECORDED, must FAIL the gate, and must NOT advance
 * the watermark.
 *
 * Measured 2026-10-02: nothing persisted the decomposer's clues (434 text
 * columns scanned for all eight clue substrings -> 0 hits; 0 tables with a
 * clue/ledger/telemetry column). IngestGateCommand failed only on fkClues,
 * so the decomposer's own coverage signals never affected the exit code while
 * it printed "Integrity OK — full schema is correct for this chunk". And the
 * watermark was persisted BEFORE the clue check, so a failing run advanced the
 * cursor over data it had just failed to place — with monotonic cursors, that
 * data falls outside the resync window and is never revisited.
 *
 * THE SCHEMA IS THE SHIPPED ONE. This suite runs `UpdateIngestor::migrationPaths()`
 * (the whole curated dial plus the off-dial entity anchors) against sqlite
 * :memory: exactly like `IngestGateCommandTest`, rather than hand-typing the
 * DDL — `ShippedStoreSchema` exists precisely because a transcribed second copy
 * of a migration silently drifts.
 *
 * FK ENFORCEMENT IS NOT ASSERTED and is not needed: the clue this suite drives
 * is raised by the DECOMPOSER (a constructor the catalog does not know), which
 * never reaches an insert at all. `foreign_key_constraints` is off here, so
 * nothing in this file may be read as a statement about FK behaviour.
 *
 * @property ?ArrayRedis $redis
 */
final class IngestGateCommandClueAccountingTest extends TestbenchTestCase
{
    private const ACCOUNT = 42;

    private const PRIOR_PTS = 3;

    private const NEXT_PTS = 11;

    private ?ArrayRedis $redis = null;

    protected function getApplicationBasePath()
    {
        return dirname(__DIR__, 2);
    }

    protected function getPackageProviders($app): array
    {
        return [TeleframeServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    /**
     * A constructor the catalog does not know produces the decomposer's OWN
     * clue — "cannot place the fact" — and no row. That is the coverage gap
     * the gate used to print and then forgive.
     */
    public function test_a_decomposer_coverage_clue_is_persisted_and_fails_the_gate(): void
    {
        $this->migrateMirror();
        $this->bind($this->fetcher(mysteryCtor: true));

        $this->artisan('teleframe:ingest:gate', ['account' => (string) self::ACCOUNT])
            ->doesntExpectOutputToContain('Integrity OK')
            ->assertExitCode(1);

        // (1) PERSISTED. The clue is a row, in its OWN bucket. The bucket is
        // asserted as 'clues' and not as 'fkClues' on purpose: this fault never
        // reached an insert, and filing a decomposer coverage gap under the
        // foreign-key axis would send the next reader to the relationship graph
        // instead of to the catalog.
        $rows = DB::table('tf_ingest_clues')
            ->where('account_id', self::ACCOUNT)
            ->where('bucket', 'clues')
            ->get();

        self::assertCount(1, $rows, 'a decomposer coverage gap must be persisted exactly once');
        self::assertSame(
            'tf_messages',
            $rows[0]->table,
            'the table name is parsed out of the clue\'s "{$table}: …" prefix so a ledger row can be grouped by table',
        );
        self::assertStringContainsString(
            "constructor 'messageMystery' not found in catalog",
            $rows[0]->detail,
            'the persisted detail is the clue STRING, not a paraphrase of it',
        );

        self::assertSame(
            0,
            (int) DB::table('tf_ingest_clues')->where('account_id', self::ACCOUNT)->where('bucket', 'fkClues')->count(),
            'a decomposer coverage gap is NOT an fkClue; the buckets stay distinct axes',
        );

        // (3) THE WATERMARK DID NOT MOVE. A failing chunk must leave the cursor
        // where it was, or the chunk falls outside the next resync window and
        // is never looked at again.
        self::assertSame(
            (string) self::PRIOR_PTS,
            $this->redis->hget('tg:pts:'.self::ACCOUNT, 'pts'),
            'a failing run must not advance the watermark over data it just failed to place',
        );
    }

    /**
     * The positive control: a clean chunk must still advance, or Task 1.2 has
     * broken the ingest loop rather than instrumented it.
     */
    public function test_a_chunk_with_no_clues_advances_the_watermark(): void
    {
        $this->migrateMirror();
        $this->bind($this->fetcher());

        $this->artisan('teleframe:ingest:gate', ['account' => (string) self::ACCOUNT])
            ->expectsOutputToContain('Integrity OK')
            ->assertExitCode(0);

        self::assertSame(
            (string) self::NEXT_PTS,
            $this->redis->hget('tg:pts:'.self::ACCOUNT, 'pts'),
            'a clean run still advances the cursor — the fix instruments the loop, it does not stall it',
        );
        self::assertSame(
            0,
            (int) DB::table('tf_ingest_clues')->where('account_id', self::ACCOUNT)->count(),
            'a clean run records no clues, so the ledger is a list of defects rather than noise',
        );
    }

    /**
     * HALF ONE OF THE WATERMARK SPLIT: `unmappedClues` ALONE advances the
     * cursor while still failing the run.
     *
     * The load-bearing fact is in
     * `tests/Schema/UnmappedMirrorFieldIsAModellingGapTest.php:22-24`: a TL
     * payload legitimately carrying a field the curated DDL does not model is
     * NORMAL for a mirror — the wire runs ahead of the dial. So this bucket is
     * the EXPECTED one on live traffic, not a fault, and the earlier rule
     * ("a failing chunk does not advance the watermark") turned it into a
     * re-fetch loop: the dial does not change between two runs of this command,
     * so a hold there strands the account on one unfixable chunk forever and
     * never lets it reach the chunks behind it.
     *
     * The bucket is still counted in the exit condition. FAILING and HOLDING
     * are two decisions and this run makes both: exit 1, because the operator
     * still has to model the field, and the cursor advances, because no retry
     * will ever close the gap.
     *
     * HOW THE BUCKET IS REACHED. The catalog is a SUBSET of the shipped DDL
     * today (`MirrorResolverKeyMatchesShippedDdlTest` pins that), so the
     * "column is not in the shipped DDL" arm cannot fire from a current host.
     * The one shipped producer left is the search-index arm of
     * `MirrorFactWriter::indexSearchRow()`, and it fires on the most ordinary
     * production state there is: a host that has not yet run the newest
     * migration, so `tf_message_search` exists but has no `token` column. That
     * is a LAGGING DDL, produced here by recreating that one derived-index
     * table in its older shape — a deliberate divergence, and the only one in
     * this file.
     */
    public function test_an_unmapped_field_alone_fails_the_run_but_advances_the_watermark(): void
    {
        $this->migrateMirror();
        $this->lagTheSearchIndexDdl();
        $this->bind($this->fetcher());

        // FAILING is unchanged: all four buckets stay in the exit condition,
        // and a modelling gap the gate forgives is a dropped field nothing
        // else reports.
        $this->artisan('teleframe:ingest:gate', ['account' => (string) self::ACCOUNT])
            ->doesntExpectOutputToContain('Integrity OK')
            ->expectsOutputToContain('unmapped fields are a modelling gap; watermark advanced')
            ->assertExitCode(1);

        self::assertSame(
            1,
            (int) DB::table('tf_ingest_clues')
                ->where('account_id', self::ACCOUNT)
                ->where('bucket', 'unmappedClues')
                ->count(),
            'the unmapped bucket is the one that fired, and it is persisted under its own name',
        );
        self::assertSame(
            0,
            (int) DB::table('tf_ingest_clues')
                ->where('account_id', self::ACCOUNT)
                ->where('bucket', 'clues')
                ->count(),
            'this run fired ONLY the unmapped bucket — otherwise it is not the case under test',
        );

        // ADVANCING is the half that is new. Without it every account on a
        // lagging dial re-fetches the same chunk forever.
        self::assertSame(
            (string) self::NEXT_PTS,
            $this->redis->hget('tg:pts:'.self::ACCOUNT, 'pts'),
            'a modelling gap no retry can close must not pin the monotonic cursor',
        );
    }

    /**
     * HALF TWO OF THE SAME SPLIT: any OTHER bucket holds the cursor.
     *
     * The decomposer's coverage bucket stands in for `fkClues` and `valueClues`
     * here because it is the one of the three a `updates.difference` fixture can
     * raise deterministically — a constructor no catalog entry knows reaches
     * the decomposer and never reaches an insert, so no FK, truncation or key
     * arm is involved and nothing but that one bucket can fire. The rule under
     * test is bucket IDENTITY, not the particular bucket: `unmappedClues` alone
     * advances, and this one does not.
     *
     * A hold is right for these three because each is transient by nature — a
     * missing foreign-key row, a truncating column, a constructor the catalog
     * has not learned — so a later run over the same chunk can genuinely place
     * what this one could not. Refusing to move is what lets that later run see
     * the chunk at all.
     */
    public function test_a_decomposer_coverage_clue_still_holds_the_watermark(): void
    {
        $this->migrateMirror();
        $this->bind($this->fetcher(mysteryCtor: true));

        $this->artisan('teleframe:ingest:gate', ['account' => (string) self::ACCOUNT])
            ->expectsOutputToContain('the watermark is NOT advanced')
            ->doesntExpectOutputToContain('watermark advanced so the account is not stuck')
            ->assertExitCode(1);

        self::assertSame(
            (string) self::PRIOR_PTS,
            $this->redis->hget('tg:pts:'.self::ACCOUNT, 'pts'),
            'a retry-fixable bucket must hold the cursor, or the chunk that could be placed later never comes back',
        );
    }

    /**
     * `--only` still means "verify, do not persist", and it outranks the
     * unmapped-bucket advance: a dry run that moves the cursor is not a dry
     * run. Asserted on the unmapped-only case because that is the ONE path
     * where the command now persists inside a FAILING branch, so it is the only
     * place the option can be silently ignored.
     */
    public function test_only_does_not_persist_even_on_the_unmapped_only_path(): void
    {
        $this->migrateMirror();
        $this->lagTheSearchIndexDdl();
        $this->bind($this->fetcher());

        $this->artisan('teleframe:ingest:gate', [
            'account' => (string) self::ACCOUNT,
            '--only' => true,
        ])->assertExitCode(1);

        self::assertSame(
            (string) self::PRIOR_PTS,
            $this->redis->hget('tg:pts:'.self::ACCOUNT, 'pts'),
            '--only must outrank the unmapped-bucket advance: the watermark is still not persisted',
        );
    }

    /**
     * Put the host on a LAGGING dial: `tf_message_search` as it shipped BEFORE
     * the `token` column existed. `MirrorFactWriter::indexSearchRow()` still
     * finds the table, still calls the indexer, and the insert raises — which
     * the writer degrades into exactly one `unmappedClues` entry instead of
     * taking the chunk down with it.
     */
    private function lagTheSearchIndexDdl(): void
    {
        Schema::drop('tf_message_search');
        Schema::create('tf_message_search', function (Blueprint $table) {
            $table->unsignedBigInteger('account_id');
            $table->integer('id');
            $table->primary(['account_id', 'id'], 'tf_message_search_pk');
        });
    }

    private function migrateMirror(): void
    {
        $this->artisan('migrate', [
            '--force' => true,
            '--realpath' => true,
            '--path' => UpdateIngestor::migrationPaths(),
        ])->assertExitCode(0);
    }

    /**
     * Bind the two seams the gate owns: the wire fetcher and the watermark
     * store. The watermark is seeded with a PRIOR state, so "unchanged" is a
     * value the test can read rather than an absence it has to infer.
     */
    private function bind(ChunkFetcher $fetcher): void
    {
        $this->app->bind(
            IngestGateCommand::FETCHER_KEY,
            fn (): callable => fn (int $accountId): ChunkFetcher => $fetcher,
        );
        $this->app->singleton(PtsWatermark::class, function () {
            $redis = new ArrayRedis;
            $watermark = new PtsWatermark($redis);
            $watermark->put(self::ACCOUNT, ['pts' => self::PRIOR_PTS, 'date' => 1, 'qts' => 1, 'seq' => 1]);
            $this->redis = $redis;

            return $watermark;
        });
    }

    /**
     * A `updates.difference` fixture. With `$mysteryCtor` it carries one extra
     * `new_messages` entry whose constructor is in NO catalog entry, which is
     * the cheapest reproducible way to make the DECOMPOSER — not the writer —
     * raise a coverage clue. The rest of the chunk is well formed, so the run
     * lands real rows and the failure is attributable to that one clue alone.
     */
    private function fetcher(bool $mysteryCtor = false): ChunkFetcher
    {
        return new class($mysteryCtor, self::NEXT_PTS) implements ChunkFetcher
        {
            public function __construct(
                private readonly bool $mysteryCtor,
                private readonly int $nextPts,
            ) {}

            public function getState(): array
            {
                return ['pts' => $this->nextPts, 'date' => 1726000000, 'qts' => 2, 'seq' => 5];
            }

            public function getDifference(array $state, ?array $priorState = null): array
            {
                $messages = [
                    [
                        '_' => 'message',
                        'id' => 7,
                        'peer_id' => ['_type' => 2, '_id' => 900],
                        'date' => 1726000000,
                        'message' => 'gate proof',
                    ],
                ];

                if ($this->mysteryCtor) {
                    $messages[] = ['_' => 'messageMystery', 'id' => 8, 'date' => 1726000000];
                }

                return [
                    '_' => 'updates.difference',
                    'new_messages' => $messages,
                    'users' => [
                        ['_' => 'user', 'id' => 101, 'verified' => true],
                    ],
                    'chats' => [
                        ['_' => 'chat', 'id' => 900, 'title' => 'gate lab', 'participants_count' => 3, 'date' => 1726000000, 'version' => 1],
                    ],
                    'state' => [
                        '_' => 'updates.state',
                        'pts' => $this->nextPts, 'qts' => 2, 'date' => 1726000000, 'seq' => 5,
                    ],
                ];
            }
        };
    }
}
