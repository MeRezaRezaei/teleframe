<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe;

use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUser;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;

/**
 * The package's public face (plan Task 5): a thin, container-resolvable
 * wrapper over the ingest surfaces — updates (always become instances),
 * method responses (route-deduped), and entity aggregation lookups.
 *
 * Resolve it from the container (bound as a singleton by the service
 * provider); see docs/ingest.md for the tenancy model, route semantics,
 * events and idempotency guarantees.
 */
final class Teleclient
{
    public function __construct(
        private readonly UpdateIngestor $ingestor,
        private readonly EntityAggregator $entities,
    ) {}

    /**
     * Ingest a raw update payload (teleframe truth: snake keys, `_`
     * constructor name). Updates always become instances — they never
     * touch routes.
     *
     * @param  array<string, mixed>  $update
     */
    public function ingest(array $update, int $accountId): ?TfMirrorModel
    {
        $root = $this->ingestor->ingest($update, $accountId);

        return $root instanceof TfMirrorModel ? $root : null;
    }

    /**
     * Ingest a raw method response, route-deduped: a (method, params,
     * account) combination that already answered resolves to the stored
     * instance instead of rewriting; update-kind payloads bypass routes
     * and always become instances.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?TfMirrorModel
    {
        $root = $this->ingestor->ingestResponse($method, $params, $response, $accountId);

        return $root instanceof TfMirrorModel ? $root : null;
    }

    /**
     * The anchor for a referenced user under a tenant, with its CURRENT
     * instance loaded as `currentInstance` — null when the tenant never
     * saw the user or its current instance is deleted.
     */
    public function user(int $accountId, int $tgId): ?TfUser
    {
        return $this->entities->user($accountId, $tgId);
    }
}
