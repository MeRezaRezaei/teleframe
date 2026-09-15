<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe;

use Illuminate\Database\Eloquent\Model;
use MeRezaRezaei\Teleframe\Ingest\EntityAggregator;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TlUser;

/**
 * The package's public face (plan Task 5): a thin, container-resolvable
 * wrapper over the ingest surfaces — updates (null when the constructor
 * has no curated mirror surface), method responses (route-deduped), and
 * entity aggregation lookups.
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
     * constructor name). Updates bypass routes.
     *
     * @param  array<string, mixed>  $update
     * @return Model|null the hydrated curated root (TfMessage / TfUpdate),
     *                    or null when the constructor has no curated mirror
     *                    surface — nothing storeable.
     */
    public function ingest(array $update, int $accountId): ?Model
    {
        return $this->ingestor->ingest($update, $accountId);
    }

    /**
     * Ingest a raw method response, route-deduped: a (method, params,
     * account) combination that already answered resolves to the stored
     * instance instead of rewriting.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     * @return Model|null the hydrated curated root, or null when the
     *                    response carries no curated surface.
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?Model
    {
        return $this->ingestor->ingestResponse($method, $params, $response, $accountId);
    }

    /**
     * The anchor for a referenced user under a tenant, with its CURRENT
     * instance loaded as `currentInstance` — null when the tenant never
     * saw the user or its current instance is deleted.
     */
    public function user(int $accountId, int $tgId): ?TlUser
    {
        return $this->entities->user($accountId, $tgId);
    }
}
