<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Laravel\Http\Controllers\Dashboard;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use MeRezaRezaei\Teleframe\Bus\RedisConnectionContract;
use MeRezaRezaei\Teleframe\Supervisor\OpsReadModel;
use Throwable;

/**
 * GET <prefix>/ops — the operations surface: recent and failed work, read
 * from state the package ALREADY records (the ingest strike ledger, the
 * dead-letter stream, the stream backlog) plus whatever supervision rows the
 * host publishes.
 *
 * Same read-model the CLI's `--status` prints, so the page and the terminal
 * can never disagree. Protected by the host's own guard like every other
 * dashboard route (default `web`+`auth`).
 *
 * Unconfigured-safe: with no Redis seam bound the snapshot answers
 * `{"bus": false, ...}` — a 200 with an honest "nothing is reading" rather
 * than a 500 an operator has to interpret.
 */
final class OpsController extends Controller
{
    public function show(): JsonResponse
    {
        return new JsonResponse($this->readModel()->snapshot()->toArray());
    }

    private function readModel(): OpsReadModel
    {
        $redis = null;
        $supervised = null;

        try {
            if (app()->bound(OpsReadModel::REDIS_KEY)) {
                $bound = app()->make(OpsReadModel::REDIS_KEY);
                $redis = $bound instanceof RedisConnectionContract ? $bound : null;
            }

            if (app()->bound(OpsReadModel::SUPERVISOR_STATE_KEY)) {
                $bound = app()->make(OpsReadModel::SUPERVISOR_STATE_KEY);
                if (is_callable($bound)) {
                    $supervised = $bound;
                }
            }
        } catch (Throwable) {
            // A broken seam degrades the snapshot, never the endpoint.
        }

        return new OpsReadModel($redis, $supervised);
    }
}
