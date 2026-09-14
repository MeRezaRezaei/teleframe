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
            'emitted' => $emitted,
        ];
    }
}
