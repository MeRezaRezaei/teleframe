<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\MirrorResultIngester;
use MeRezaRezaei\Teleframe\Ingest\MirrorResultPolicy;
use MeRezaRezaei\Teleframe\Ingest\MirrorUpdateIngester;
use MeRezaRezaei\Teleframe\Ingest\RoutingEventGateway;
use MeRezaRezaei\Teleframe\Ingest\SelfOriginatedClassifier;
use MeRezaRezaei\Teleframe\Ingest\UpdateRouter;
use MeRezaRezaei\Teleframe\Ingest\UpdateRoutingCache;
use MeRezaRezaei\Teleframe\Laravel\Models\TfIngestClue;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Default mirror-ingester factory — the daemon's daily observer, wired.
 *
 * Owner verbatim 2026-09-14: "the regular one is going to let us observe
 * the telegram update push them into redis using redis and observer lets
 * us keep our relational database updated with the latest telegram update
 * and the data after update comes back in shape of eloquent models".
 *
 * Builds the full NF5 mirror pipeline from the committed schema artifacts
 * (the same pure construction the gate command and MirrorChunkSync tests
 * use) and returns the callable seam the bus consumer accepts:
 * callable(array $update, int $accountId): void. The pipeline decides the
 * fact table from the constructor (tf_messages / tf_users / tf_chats) and
 * hydrates the stored model for the UpdateStored event (Eloquent models
 * back — the query surface the verbatim promises).
 */
final class MirrorIngesterFactory
{
    /** @var array<string, string>|null tf table => model FQCN, built once */
    private static ?array $modelIndex = null;

    public static function build(
        ?Dispatcher $events = null,
        ?CacheInterface $sends = null,
        ?UpdateRoutingCache $routingCache = null,
    ): callable {
        $tl = SchemaArtifacts::tl();
        $catalogPath = SchemaArtifacts::catalog();

        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load($catalogPath, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);
        $router = new UpdateRouter(DB::connection(), $routingCache);
        $decomposer = new MirrorFactDecomposer($resolver, $catalog);
        $writer = new MirrorFactWriter;
        $walker = new MirrorNestedSourceWalker($catalog);

        $ingester = new MirrorUpdateIngester(
            $decomposer,
            $writer,
            new SelfOriginatedClassifier($router, $sends),
            new RoutingEventGateway($router, $events),
        );

        return static function (array $update, int $accountId) use ($ingester, $catalog, $resolver, $decomposer, $writer, $walker): void {
            // ONE connection, resolved once: the transaction below and every
            // write inside it must be the SAME connection, or the transaction
            // would wrap nothing.
            $db = DB::connection();

            // THE CLUE LEDGER IS ACCUMULATED OUTSIDE THE TRANSACTION AND WRITTEN
            // AFTER IT — this is the whole of fix round 1/5 finding B2, and it is
            // the opposite of what 714bfff1 did.
            //
            // 714bfff1 called recordClues() INSIDE the transaction, which
            // committed the ledger with the rows or rolled it back with them.
            // That is a defensible-looking choice with the wrong property: any
            // fault that raises out of write() — a serialization failure, a
            // dead connection, a disk error on the transaction's own COMMIT —
            // rolls the transaction back, and the clue that would have EXPLAINED
            // the rollback is destroyed by the same event. The ledger is only
            // useful for writes that did not fully happen, and writing it
            // inside the boundary made it a record of the writes that worked.
            // The gate's ledger already survives rollback (IngestGateCommand
            // records post-commit); the two surfaces disagreed about the property
            // the migration's own docblock promises, and this line is where the
            // bus path is brought into line.
            //
            // Hence: the closure only ACCUMULATES into this by-reference array,
            // and the single write happens in the `finally` below. `finally`, not
            // a plain statement after the call, because the raise this exists to
            // survive IS the case: a plain statement after $db->transaction()
            // would not run when the transaction threw, which is the only moment
            // the ledger is worth having.
            $clueReport = self::emptyClueReport();

            try {
                // THE TRANSACTION BOUNDARY IS THE ENTRY, not write() and not the
                // per-fact ingest. A single bus entry produces SEVERAL write()
                // calls — ingestNestedSources() runs one per nested fact BEFORE
                // the top-level ingest() runs another — so a per-fact boundary
                // would leave a nested fact committed when the top-level write
                // then throws. That half-written mirror is worse than the raise it
                // replaces, because nothing reports it. Matches the live path's
                // precedent at UpdateIngestor::ingest(), which wraps the whole
                // root ingest in DB::transaction for the same reason. WHAT THE
                // BOUNDARY DOES NOT DO is carry the ledger: see above.
                $db->transaction(static function () use ($update, $accountId, $db, $ingester, $catalog, $resolver, $decomposer, $writer, $walker, &$clueReport): void {
                    $payload = self::unwrapUpdate($update);

                    $ctor = (string) ($payload['_'] ?? '');
                    $entry = $ctor === '' ? null : $catalog->tableForCtor($ctor);

                    // Declared NESTED SOURCES: tables the catalog says are filled from a
                    // field of another constructor, which top-level ctor routing can
                    // never reach. Collected and written BEFORE the top-level
                    // early-return, because an update carrying only a nested fact
                    // (`updateChannelParticipant` has no mirror table of its own) would
                    // otherwise be dropped whole. Its return is ACCUMULATED, not
                    // discarded — see ingestNestedSources() for why that used to be
                    // a silent loss of the whole clue ledger for nested facts.
                    $clueReport = self::mergeClueReports(
                        $clueReport,
                        self::ingestNestedSources(
                            $writer,
                            $decomposer,
                            $db,
                            $accountId,
                            $walker->collect($update),
                        ),
                    );

                    if ($entry === null) {
                        return; // no mirror table for this constructor — nothing to store
                    }

                    $modelClass = self::modelClassFor($entry->tfName);
                    $table = $resolver->resolveAll([$entry->tfName])[0];

                    // The result IS ACCUMULATED. This call used to discard it,
                    // which dropped all four clue buckets of every bus update on
                    // the floor: the mirror had no record, anywhere, of a fact it
                    // could not place.
                    $clueReport = self::mergeClueReports($clueReport, $ingester->ingest(
                        $db,
                        $accountId,
                        $payload,
                        $entry->tfName,
                        static function (array $payload) use ($modelClass, $accountId, $table) {
                            unset($payload['_']); // ctor marker is not a column
                            $payload['account_id'] = $accountId;

                            // Peer fields arrive on the wire as a single object
                            // (peerChannel#channel_id …); the model stores the canonical
                            // _type/_id halves. Expand so the hydrated model — the
                            // verbatim's query surface — carries the same peer shape
                            // the mirror rows do. Missing/unresolvable peers are left
                            // unset (the decomposer already surfaced the clue).
                            $payload = self::expandPeers($payload, $table->peerColumns);

                            return (new $modelClass)->forceFill($payload);
                        },
                    ));
                });
            } finally {
                // OUTSIDE the transaction, in a finally, on the SAME connection as
                // the rows. Same connection because a clue filed under one tenant's
                // connection and written on another's is a clue about the wrong
                // database; outside the transaction because see above. Non-fatal by
                // construction (recordClues swallows its own faults), so a host
                // whose migration predates the ledger still ingests.
                self::recordClues($db, $accountId, $clueReport);
            }
        };
    }

    /**
     * The RPC-RESULT mirror route — the second ingest surface, alongside the
     * UPDATE callable this class already returns.
     *
     * `build()` above is fed only by the Redis bus, so it only ever sees
     * updates. A constructor Telegram delivers solely as an RPC result
     * (`userFull` via `users.getFullUser`, `savedStarGift` via
     * `payments.getSavedStarGifts`, ...) therefore had shipped DDL, a correct
     * model and a correct write path — and no data, ever. This callable is
     * that missing route.
     *
     * It is deliberately OPT-IN per call, not always-on: it is
     * callable(array $result, int $accountId): array, so the caller states the
     * account the result was fetched FOR (the mirror's scoping key, which the
     * transport cannot supply on its own) and hands over the decoded result.
     * An always-on route inside the client would write rows for every method
     * the operator ever calls — including calls nobody asked to be mirrored —
     * and would have no account id to scope them with, since `dispatch()` calls
     * `user()` with no arguments (`TeleframeClient::dispatch`, line 68). This
     * shape keeps the behaviour change opt-in and the scope explicit.
     *
     * The SAME walker and the SAME decomposer are reused: this returns a
     * `MirrorResultIngester` built from the identical `$walker` / `$decomposer`
     * / `$writer` triple constructed above. There is no second write path.
     *
     * CALLED BY `buildResultMirror()` below, which is the surface hosts and
     * commands use. This raw route stays ungated on purpose: it is the write
     * seam, and a caller that has already decided to mirror holds the decision
     * itself. `tests/Ingest/MirrorResultRoutePlacesRowTest.php` proves the
     * route against the real catalog and the real shipped migration.
     *
     * @return callable(array<string, mixed>, int): array{inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    public static function buildResultRoute(): callable
    {
        $tl = SchemaArtifacts::tl();
        $scheme = TlParser::parseFile($tl);
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $ingester = new MirrorResultIngester(
            new MirrorNestedSourceWalker($catalog),
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
        );

        $db = DB::connection();

        // Same entry-level boundary as build() above, and for the same reason:
        // MirrorResultIngester::ingest() makes one write() call per nested fact,
        // so a per-fact boundary could commit fact 1 and lose fact 2.
        return static fn (array $result, int $accountId): array => $db->transaction(
            static fn (): array => $ingester->ingest(
                $db,
                $accountId,
                $result,
            ),
        );
    }

    /**
     * The POLICY-GATED result mirror — the surface a caller actually uses.
     *
     * `buildResultRoute()` above writes whatever it is handed, so something
     * has to say which method's result is worth handing it. That decision is
     * `MirrorResultPolicy`, and this callable is where it is applied:
     * `callable(string $method, array $result, int $accountId): array` returns
     * `{mirrored: bool, inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}` and writes
     * nothing at all for a method the operator has not admitted.
     *
     * WHY THE CALLER STILL SUPPLIES THE ACCOUNT: the transport cannot. The
     * mirror's scoping key is the account the result was fetched FOR, and
     * `dispatch()` calls `user()` with no arguments
     * (`TeleframeClient::dispatch`, line 68) — an always-on route inside the
     * client would have no account to scope rows with, and would write for
     * every method the operator ever calls. The account stays a parameter.
     *
     * WHY NOT THE THREE SURFACES PREVIOUSLY SURVEYED: the relay terminal
     * (`src/Teleframe/Control/RelayTerminal.php:119`) and its
     * `RelayContext` do carry an account id, but they are RELAY files — never
     * edited for this — and a relay is a generic pass-through whose callers
     * include the notify premium probe and a web client; mirroring there would
     * key off traffic rather than off an operator's decision.
     * `PremiumProbe` (`src/Teleframe/Notify/PremiumProbe.php:85`) does dial
     * `users.getFullUser`, but its transport is
     * `callable(string, array): array` with no account id, and it probes
     * arbitrary third-party user ids on the notify send path: mirroring there
     * writes rows for users the operator never named, on the hot path, and
     * swallows the failure by contract. `BackfillCommand` was rejected for
     * fetching a message-history page — correct as far as the PAGE goes, and
     * the page still is not the route's caller; what became the caller is the
     * account-profile fetch that command makes anyway, below.
     *
     * Provenance: policy default list in `MirrorResultPolicy::DEFAULT_METHODS`;
     * operator override key `teleframe.mirror.result_methods`; the live caller
     * is `src/Laravel/Console/BackfillCommand.php::mirrorAccountProfile()`.
     *
     * @return callable(string, array<string, mixed>, int): array{mirrored: bool, inserted: int, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    public static function buildResultMirror(): callable
    {
        $route = self::buildResultRoute();
        $policy = self::resultPolicy();

        return static function (string $method, array $result, int $accountId) use ($route, $policy): array {
            if (! $policy->mirrors($method)) {
                // Not admitted: the result is dropped whole, loudly in shape
                // (mirrored=false) rather than by throwing, because a caller
                // mirroring several methods must not lose the run over one.
                return ['mirrored' => false, 'inserted' => 0, 'fkClues' => [], 'unmappedClues' => [], 'valueClues' => []];
            }

            $written = $route($result, $accountId);

            return [
                'mirrored' => true,
                'inserted' => $written['inserted'],
                'fkClues' => $written['fkClues'],
                'unmappedClues' => $written['unmappedClues'],
                // This callable REBUILDS a narrower array rather than forwarding
                // the route's, so the route's valueClues — the "value does not
                // fit the column the DDL gives it" axis — must be named here or
                // it never reaches the caller. The not-admitted branch above
                // carries the same key empty, so both branches share one shape.
                'valueClues' => $written['valueClues'],
            ];
        };
    }

    /**
     * The operator's admission list, read from `teleframe.mirror.result_methods`.
     *
     * The key is deliberately NOT added to the shipped `teleframe.php`: an
     * operator who wants to narrow or widen the list publishes the config and
     * adds the key, and a host that has not touched it gets the catalog-derived
     * default. A missing key and a null key both fall back — the mirror must
     * never be left with no policy because a config file forgot a line.
     */
    private static function resultPolicy(): MirrorResultPolicy
    {
        $configured = config('teleframe.mirror.result_methods');

        if (! is_array($configured)) {
            return MirrorResultPolicy::default();
        }

        $methods = [];
        foreach ($configured as $method) {
            if (is_string($method)) {
                $methods[] = $method;
            }
        }

        return MirrorResultPolicy::admitting($methods);
    }

    /**
     * Expand peer-field half names (peer_id_type / peer_id_id) from the
     * single payload field they encode (peer_id). Accepts the wire ctor
     * object and the canonical _type/_id pair alike (PeerShapeTool), and
     * drops the source array field so no stray attribute reaches the model.
     *
     * @param  array<string, mixed>  $payload
     * @param  list<array{kind: 'type'|'id', name: string}>  $peerColumns
     * @return array<string, mixed>
     */
    private static function expandPeers(array $payload, array $peerColumns): array
    {
        foreach ($peerColumns as $half) {
            if ($half['kind'] !== 'type') {
                continue; // the id half rides along with its type twin
            }
            $name = $half['name'];
            if (! str_ends_with($name, '_type')) {
                continue;
            }
            $stem = substr($name, 0, -5);
            // Canonical peer mapping: `peer_type`/`peer_id` encode payload `peer_id`.
            $field = $stem === 'peer' ? 'peer_id' : $stem;
            $value = $payload[$field] ?? null;
            if (! is_array($value)) {
                continue; // decomposer already clues; model stays unset
            }

            [$type, $id] = PeerShapeTool::normalize($value);
            if ($type === 0 && $id === 0) {
                continue; // unresolvable — do not hydrate 0/0 into the model
            }
            $payload[$name] = $type;
            $idColumn = $stem.'_id';
            $payload[$idColumn] = $id;
            if ($field !== $idColumn) {
                unset($payload[$field]); // canonical peer: id column reuses the wire key
            }
        }

        return $payload;
    }

    /**
     * Decompose and write the facts a payload carries NESTED, per the catalog's
     * declared `sources`.
     *
     * A nested fact is a fact, not an update: it is stored (row existence = fact
     * existence) but it never routes or emits `UpdateStored` — there is no peer
     * routing decision and no app event owed for a value that arrived inside
     * somebody else's constructor. FK/NOT NULL failures surface as the same
     * clue strings the top-level path emits; they are not swallowed.
     *
     * It RETURNED `void` until 2026-10-02, and the return was the whole
     * problem: `write()` hands back four clue buckets, and discarding them here
     * meant every nested fact's FK/value/unmapped clues never reached any
     * caller — there was nothing to reach, because this method answered
     * nothing. A nested fact that failed to place was as invisible as the
     * top-level ones. It now returns the aggregate, and the caller records it.
     *
     * `clues` is the decomposer's own coverage bucket; the writer does not
     * raise it, so the decomposer's `$fact_rows['clues']` is merged in here
     * rather than left behind.
     *
     * @param  list<array{table: string, payload: array<string, mixed>}>  $facts
     * @return array{inserted: int, clues: list<string>, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    private static function ingestNestedSources(
        MirrorFactWriter $writer,
        MirrorFactDecomposer $decomposer,
        ConnectionInterface $db,
        int $accountId,
        array $facts,
    ): array {
        $total = ['inserted' => 0, 'clues' => [], 'fkClues' => [], 'unmappedClues' => [], 'valueClues' => []];

        foreach ($facts as $fact) {
            $fact_rows = $decomposer->decompose($fact['table'], $accountId, $fact['payload']);
            $total['clues'] = [...$total['clues'], ...$fact_rows['clues']];

            if ($fact_rows['rows'] === []) {
                continue;
            }
            // The write's return IS captured — it carries the other three
            // buckets. Discarding it (as this line did) lost every FK and value
            // fault a nested fact produced.
            $write = $writer->write($db, $fact_rows['rows']);
            $total['inserted'] += $write['inserted'];
            $total['fkClues'] = [...$total['fkClues'], ...$write['fkClues']];
            $total['unmappedClues'] = [...$total['unmappedClues'], ...$write['unmappedClues']];
            $total['valueClues'] = [...$total['valueClues'], ...$write['valueClues']];
        }

        return $total;
    }

    /**
     * Append every clue of an ingest report to `tf_ingest_clues`.
     *
     * ## WHERE THIS RUNS, AND WHY THAT IS THE POINT (fix round 1/5, B2)
     *
     * The callers write the ledger AFTER the rows' transaction has returned or
     * thrown — never inside it. The earlier arrangement, which recorded inside
     * the transaction, guaranteed that a clue could not outlive a rolled-back
     * write, and that guarantee was worthless: the clue that names a fault is
     * raised BY the write, so a rollback destroyed the explanation along with
     * the rows. A ledger whose entire subject is "what failed to store" must
     * survive the failure. `IngestGateCommand` has always recorded post-commit;
     * this makes the bus path agree with it rather than disagree about the
     * property the migration's docblock promises.
     *
     * Two consequences, both deliberate:
     *
     *  - THE ROWS MAY NOT EXIST. A recorded clue can describe a transaction that
     *    rolled back whole. That is not a defect in the ledger, it is the only
     *    interesting thing it can say. What is worth being precise about is what
     *    `inserted` does and does not tell a reader of this method — MEASURED
     *    2026-10-04, because the sentence that used to stand here was false on
     *    both counts: "inserted is what distinguishes the two cases: a clue beside
     *    `inserted > 0` is a per-row degradation, a clue beside a rolled-back entry
     *    is the reason the entry is missing."
     *
     *    (a) It does NOT distinguish them. The accumulator is filled INSIDE the
     *        transaction, so a report carrying `inserted > 0` beside a clue is
     *        exactly what a rollback after a successful write looks like: the
     *        `UpdateStored` emit, the router lookup or the model's hydration can
     *        all raise after `write()` returned, and the report that survives into
     *        this `finally` still says the rows were placed.
     *    (b) It is NOT a row count either. `MirrorFactWriter::write()` counts
     *        STATEMENTS PLACED: three facts sharing one primary key return
     *        `inserted=3` with four empty buckets while the table holds one row.
     *
     *    So this ledger entry says what its own clue strings say and nothing more.
     *    A reader who needs to know whether the rows are there must ask the
     *    database, which is the only place the answer is kept.
     *  - THE WRITE MAY BE IMPOSSIBLE. When the rollback left the connection
     *    unusable (Postgres leaves a session in an aborted state until ROLLBACK,
     *    and a dead connection stays dead), the insert below fails too. That is
     *    why this method is non-fatal and why its failures are now LOGGED rather
     *    than swallowed (fix round 1/5, B4).
     *
     * @param  array<string, mixed>  $report
     */
    private static function recordClues(ConnectionInterface $db, int $accountId, array $report): void
    {
        if (! self::hasClues($report)) {
            return;
        }

        try {
            TfIngestClue::recordReport($db, $report, $accountId);
        } catch (QueryException|\PDOException $e) {
            // Never fatal: losing the ledger must not stop the mirror. But NOT
            // SILENT either — 714bfff1 caught `\Throwable` with an empty body,
            // which is the same hole this file exists to close: a host that never
            // ran the migration got no ledger AND no log line, so it was
            // byte-for-byte the pre-task state with nothing reporting it. Narrowed
            // to the two database exception types because those are the only
            // faults a missing/unmigrated table raises, and a TypeError or a
            // bad-argument fault inside recordReport() is a REAL bug that must
            // not be laundered into "the ledger is best-effort".
            self::logger()?->warning('teleframe: the ingest clue ledger could not be written', [
                'account_id' => $accountId,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * The PSR-3 seam, resolved the way the rest of the provider does
     * (`$app->make(LoggerInterface::class)`), but WITHOUT the container being a
     * hard dependency of this static helper.
     *
     * (`TeleframeServiceProvider`) binds `LoggerInterface` to a resolved logger
     * (or a NullLogger when the host configured none), so a Laravel host always
     * has one; `MirrorIngesterFactory` is also reachable from plain-PHP paths
     * with no application at all, which is why this is a `bound()` check and not
     * an unconditional `make()`. (`Container::getInstance()` instantiates an empty
     * container when none exists; that is inert here — `bound()` on it is false,
     * so the answer is `null` — and the method is only reached from a real ingest,
     * which needs `DB::` and therefore a real application.) MEMOISED so the
     * container is consulted once per process rather than once per failed ledger
     * write.
     */
    private static function logger(): ?LoggerInterface
    {
        static $resolved = false;
        static $logger = null;

        if ($resolved) {
            return $logger;
        }

        $resolved = true;
        $container = Container::getInstance();

        if ($container->bound(LoggerInterface::class)) {
            // No instanceof re-check: `bound()` plus the interface-typed `make()`
            // is the whole contract, and a container that lies about what it has
            // bound would be a host defect that should surface, not be smoothed
            // over by a cast.
            $logger = $container->make(LoggerInterface::class);
        }

        return $logger;
    }

    /**
     * The empty four-bucket accumulator a bus entry starts from.
     *
     * @return array{inserted: int, clues: list<string>, fkClues: list<string>, unmappedClues: list<string>, valueClues: list<string>}
     */
    private static function emptyClueReport(): array
    {
        return ['inserted' => 0, 'clues' => [], 'fkClues' => [], 'unmappedClues' => [], 'valueClues' => []];
    }

    /**
     * Fold one ingest report into the running accumulator, BUCKET BY BUCKET over
     * the whole bucket list rather than naming keys.
     *
     * The point is that this class cannot forget a bucket: `inserted` sums and
     * every entry of `TfIngestClue::buckets()` is concatenated, so a new bucket
     * added to the model is folded in here without anyone remembering to edit
     * this method. The bug being repaired was four times over a hand-written
     * `array_merge()` of named keys, each one a place to forget the next.
     *
     * @param  array<string, mixed>  $into
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private static function mergeClueReports(array $into, array $report): array
    {
        $into['inserted'] = (int) ($into['inserted'] ?? 0) + (int) ($report['inserted'] ?? 0);

        foreach (TfIngestClue::buckets() as $bucket) {
            $add = $report[$bucket] ?? [];

            if (! is_array($add) || $add === []) {
                continue;
            }

            /** @var list<string> $existing */
            $existing = is_array($into[$bucket] ?? null) ? $into[$bucket] : [];
            $into[$bucket] = [...$existing, ...array_values(array_filter($add, 'is_string'))];
        }

        return $into;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private static function hasClues(array $report): bool
    {
        foreach (TfIngestClue::buckets() as $bucket) {
            if (($report[$bucket] ?? []) !== []) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map a mirror tf_ table name to its curated model class. The index is
     * built once by instantiating every curated model and reading its $table
     * — deterministic regardless of the generator's singular/plural naming
     * (tf_messages → TfMessage, tf_users → TfUser, ...). Throws when the
     * table name has no model, which is a curated-artifacts regression
     * surfaced loudly on the wire.
     */
    private static function modelClassFor(string $tfName): string
    {
        if (self::$modelIndex === null) {
            self::$modelIndex = [];
            $dir = dirname(__DIR__, 1).'/Teleframe/Mirror/Models';
            foreach (glob($dir.'/*.php') ?: [] as $file) {
                $class = 'MeRezaRezaei\Teleframe\Mirror\Models\\'.basename($file, '.php');
                if (! class_exists($class)) {
                    continue; // trait/concern files are not models
                }
                $model = new $class;
                self::$modelIndex[$model->getTable()] ??= $class;
            }
        }

        return self::$modelIndex[$tfName] ?? throw new \RuntimeException("No curated mirror model for table {$tfName} (curated dial regression).");
    }

    /**
     * Update wrappers whose inner payload is the mirror's fact surface.
     * The catalog is entity-keyed (message → tf_messages, channel →
     * tf_chats, groupCall → tf_group_calls, ...) while the bus carries full
     * updates — so the seam resolves the fact table from the update's inner
     * entity, exactly like the chunk decomposer does for getDifference
     * vectors (absent fact → no row). Signal-only updates (updateChannel,
     * updateChannelTooLong, ...) carry bare ids, not facts, and deliberate
     * nothing to store.
     *
     * @var array<string, string> update constructor → inner fact field
     */
    private const UPDATE_FACT_FIELD = [
        'updateNewMessage' => 'message',
        'updateNewChannelMessage' => 'message',
        'updateEditMessage' => 'message',
        'updateEditChannelMessage' => 'message',
        'updateGroupCall' => 'call',
    ];

    /**
     * The seam accepts either an entity payload (message, channel, ...)
     * directly or an update wrapper carrying one — unwrap the wrapper to
     * its inner fact before resolving the mirror table.
     *
     * @param  array<string, mixed>  $update
     * @return array<string, mixed>
     */
    private static function unwrapUpdate(array $update): array
    {
        $ctor = (string) ($update['_'] ?? '');

        if (! isset(self::UPDATE_FACT_FIELD[$ctor])) {
            return $update; // entity payload or a non-fact update — pass through
        }

        $inner = $update[self::UPDATE_FACT_FIELD[$ctor]] ?? null;

        return is_array($inner) ? $inner : $update;
    }
}
