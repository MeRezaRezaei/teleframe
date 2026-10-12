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
     *     emitted: bool,
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

        $emitted = false;
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
            $emitted = $mode === UpdateRoutingRule::MODE_ACT_ON
                ? $this->gateway->emit($accountId, $payload, $model, $mode)
                : false;
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
            'emitted' => $emitted,
        ];
    }
}
