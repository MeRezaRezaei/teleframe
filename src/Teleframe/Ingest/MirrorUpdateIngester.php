<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Laravel\Models\UpdateRoutingRule;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;

/**
 * Mirror update ingester — the observer that keeps the relational DB updated.
 *
 * Owner verbatim 2026-09-14: "the regular one is going to let us observe
 * the telegram update push them into redis using redis and observer lets
 * us keep our relational database updated with the latest telegram update
 * and the data after update comes back in shape of eloquent models".
 *
 * One decoded Telegram update through the full mirror pipeline:
 * 1. decompose → mirror rows (facts ALWAYS stored — the first half of
 *    absolute truth);
 * 2. write into the NF5 relational DB (FK/NOT NULL failures are clues);
 * 3. classify the update ONCE (SelfOriginatedClassifier knows the out-flag
 *    group-2 default AND the per-peer routing escape hatch) and pass the
 *    decided mode to the gateway — act_on emits UpdateStored, store_only
 *    persists silently (loop prevention, cycles 8–11).
 *
 * The daily loop: poller → Redis stream → this ingester → relational DB
 * (and, for act_on facts, an app event to react on).
 */
final class MirrorUpdateIngester
{
    public function __construct(
        private readonly MirrorFactDecomposer $decomposer,
        private readonly MirrorFactWriter $writer,
        private readonly SelfOriginatedClassifier $classifier,
        private readonly RoutingEventGateway $gateway,
    ) {}

    /**
     * @param  array<string, mixed>  $payload  decoded TL update
     * @param  string  $table  mirror table for this fact
     * @param  callable(array<string, mixed>): Model  $hydrate  payload → persisted model
     * @return array{
     *     rows: int,
     *     inserted: int,
     *     clues: list<string>,
     *     fkClues: list<string>,
     *     unmappedClues: list<string>,
     *     valueClues: list<string>,
     *     emit_scheduled: bool,
     *     emit: array{routed: bool, dispatched: bool, listeners_ran: bool, scheduled: bool, outcome: string}|null,
     * }
     */
    public function ingest(
        ConnectionInterface $db,
        int $accountId,
        array $payload,
        string $table,
        callable $hydrate,
    ): array {
        $fact = $this->decomposer->decompose($table, $accountId, $payload);
        $write = $this->writer->write($db, $fact['rows']);

        $emitScheduled = false;
        $emit = null;
        // THE ONE PLACE IN THE REPO WHERE `inserted` IS ACTED ON, and the limit
        // of that predicate is worth stating where the decision is made.
        //
        // `inserted > 0` is SOUND for what it asks: "did the writer place at
        // least one statement". Measured 2026-10-04, it is also the only signal
        // available, because `MirrorFactWriter::write()` carries no converge
        // information at all — three distinct facts colliding on one shipped
        // primary key return `inserted=3` with four empty clue buckets while the
        // table holds ONE row. So this emit can fire on a write that destroyed
        // facts, and nothing downstream can tell.
        //
        // It is NOT fixed here, deliberately. The alternatives are a new key in
        // `write()`'s return carried across every hop that names the four buckets
        // explicitly, plus a decision about what "this write converged" means to
        // an app event — and the counter must NOT be turned into a row count to
        // achieve it, because a lower number here emits fewer events for work
        // that did happen. The measurements and the boundary are recorded on
        // `MirrorFactWriter::write()` and in
        // `tests/Schema/Mirror/InsertedCountsStatementsNotRowsTest.php`.
        if ($write['inserted'] > 0) {
            $mode = $this->classifier->classify($accountId, $payload);
            $model = $hydrate($payload);
            // `emitOutcome()`, not `emit()`. `emit()` IS `emitOutcome()['routed']`
            // (RoutingEventGateway.php:147), so `emit_scheduled` below is the
            // SAME fact under the name its existing contract promises — the
            // richer array is carried ALONGSIDE it, never in place of it.
            $emit = $mode === UpdateRoutingRule::MODE_ACT_ON
                ? $this->gateway->emitOutcome($accountId, $payload, $model, $mode)
                : null;
            $emitScheduled = $emit['routed'] ?? false;
        }

        return [
            'rows' => count($fact['rows']),
            'inserted' => $write['inserted'],
            'clues' => $fact['clues'],
            'fkClues' => $write['fkClues'],
            'unmappedClues' => $write['unmappedClues'],
            // This return REBUILDS a narrower array rather than forwarding
            // write()'s, so write()'s fourth key — valueClues, the "value does
            // not fit the column the DDL gives it" axis — must be named here
            // too or it is silently dropped by this layer.
            'valueClues' => $write['valueClues'],
            // `emit_scheduled`, NOT `emitted`. The rename is the fix; the
            // comment is why the old name was a lie rather than a shortcut.
            //
            // Commit 7b7b7c31 put `ShouldDispatchAfterCommit` on UpdateStored, so
            // `emit()` now hands the event to Illuminate\Events\Dispatcher,
            // which REGISTERS the listeners as a transaction callback and
            // RETURNS WITHOUT INVOKING THEM. MEASURED against a real
            // Illuminate dispatcher over :memory: SQLite with a real
            // DatabaseTransactionsManager: at the instant `emit()` returned
            // `true`, the listener log was EMPTY and `transactionLevel()` was
            // 1; the listener ran only after `commit()`, at level 0. The same
            // dispatch with NO transaction open ran the listener inline, which
            // is the control that proves the deferral comes from the open
            // transaction and not from a listener that silently does nothing.
            //
            // So the old name promised the one thing that had stopped being
            // true: `emitted => true` read as a delivery confirmation while
            // nothing had run. Nothing in this repo READS the key — the only
            // production caller, `MirrorIngesterFactory`, folds the report
            // through `mergeClueReports()`, which reads `inserted` plus the four
            // `TfIngestClue::buckets()` and structurally drops this one — so
            // the rename breaks nothing shipped, and a host that DID read it as
            // confirmation was already reading the lie.
            //
            // WHAT TRUE GUARANTEES: the routing decision was act_on, and the
            // UpdateStored event object was handed to the dispatcher. That
            // second fact is the same value as `$emit['routed']` below.
            //
            // WHAT TRUE DOES NOT GUARANTEE, and this is the point of the name:
            //   * that ANY listener has run. On the mirror path one always has
            //     NOT — `MirrorIngesterFactory` opens the transaction, so every
            //     dispatch here defers to commit.
            //   * that a dispatcher was even bound. `RoutingEventGateway` is
            //     constructed with a nullable one and `dispatchStored()` early
            //     returns on null, so a gateway with no events still returns
            //     true. "Scheduled" is therefore the routing DECISION, and the
            //     handoff is conditional on a dispatcher existing.
            //   * that any listener succeeded. A listener that throws is
            //     swallowed and logged by the gateway; it cannot make this
            //     false, because this is reported before it could ever run.
            //
            // A tri-state distinguishing "listeners ran" from "dispatch
            // scheduled" would be strictly more honest, and is NOT buildable
            // here: the gateway returns a bool and swallows the dispatch's own
            // return value, so this layer cannot observe deferral. It needs
            // RoutingEventGateway to surface the dispatch outcome, which is
            // outside this change. Until then this boolean carries exactly one
            // of the two meanings, and its name says which.
            'emit_scheduled' => $emitScheduled,

            // The richer array `emitOutcome()` returned, forwarded under its
            // own key rather than folded into `emit_scheduled` — the bool is
            // `emit['routed']` and nothing more, so the four facts that bool
            // cannot carry (dispatched, listeners_ran, scheduled, outcome) are
            // dropped here if this key goes. No shipped caller reads it yet
            // (`MirrorIngesterFactory::mergeClueReports()` folds only
            // `inserted` and the clue buckets), which is exactly why it must be
            // present: the layer that computed it can no longer be asked.
            'emit' => $emit,
        ];
    }
}
