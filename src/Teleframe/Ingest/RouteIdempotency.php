<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;
use Symfony\Component\Uid\Uuid;

/**
 * Route-table idempotency for method responses (P1 design): each stable
 * method gets a generated tl_route_<method> table whose unique route_id
 * is the idempotency key derived from (method, route key, account), so a
 * response ingested twice is a no-op instead of a duplicate. Update-kind
 * payloads never touch routes — they always become instances (Task 5
 * wires that branch via isUpdatePayload()).
 */
final class RouteIdempotency
{
    public function __construct(
        private readonly ?\Closure $now = null,
    ) {
    }

    private function now(): \DateTimeImmutable
    {
        return $this->now !== null ? ($this->now)() : new \DateTimeImmutable();
    }

    /**
     * Fixed v5 namespace for route_id derivation: route_id =
     * uuid5(NS, method|account|routeKey) — deterministic per (method,
     * params key, tenant), so uniqueness of route_id IS the dedup.
     */
    private const ROUTE_NS = '5f4a2f7a-9c1e-4b6d-8a3f-2e7c1d0b4a9e';

    /**
     * The generated route tables ship off-dial (single migration creating
     * every tl_route_* table); tests and console tooling migrate them
     * alongside the dial, following UpdateIngestor's off-dial mechanism.
     *
     * @return list<string>
     */
    public static function migrationPaths(): array
    {
        return [
            dirname(__DIR__, 3) . '/generated/migrations/2026_08_28_900633_create_tl_route_tables.php',
        ];
    }

    /**
     * Generated table for a method: messages.getHistory →
     * tl_route_messages_get_history (Naming::snake, generator-identical).
     */
    public static function tableFor(string $method): string
    {
        return 'tl_route_' . Naming::snake($method);
    }

    /**
     * Deterministic route key for a method invocation: sorted-params JSON
     * namespaced by method, stable across key order at any depth.
     *
     * @param array<string, mixed> $params
     */
    public static function keyFor(string $method, array $params): string
    {
        $json = json_encode(
            ['method' => $method, 'params' => self::sortedKeys($params)],
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION,
        );
        if ($json === false) {
            throw new \InvalidArgumentException(
                'RouteIdempotency: params are not JSON-encodable (invalid UTF-8?)',
            );
        }

        return $json;
    }

    /**
     * Update-kind payloads (top constructor prefixed update/updates)
     * always become instances and bypass routes; method responses
     * (messages.messages, user, ...) go through route dedup.
     *
     * @param array<string, mixed> $payload
     */
    public static function isUpdatePayload(array $payload): bool
    {
        $constructor = (string) ($payload['_'] ?? '');

        return $constructor !== '' && str_starts_with($constructor, 'update');
    }

    /**
     * Has this (method, route key) already produced a stored response
     * for the account? Pure lookup on the generated route table.
     */
    public function seen(string $method, string $routeKey, int $accountId): bool
    {
        return $this->storedId($method, $routeKey, $accountId) !== null;
    }

    /**
     * Id of the instance this route is answered by (the row PK doubles as
     * the pointer to the stored response), or null when unseen — the
     * response-routing wiring resolves the stored model from it.
     */
    public function storedId(string $method, string $routeKey, int $accountId): ?int
    {
        $id = DB::table(self::tableFor($method))
            ->where('route_id', self::routeId($method, $routeKey, $accountId))
            ->value('id');

        return $id === null ? null : (int) $id;
    }

    /**
     * Record the route as answered by the instance with id $tlId (the
     * row PK doubles as the pointer to the stored response). Marking an
     * already-seen route is a no-op — that is the whole point.
     */
    public function mark(string $method, string $routeKey, int $accountId, int $tlId): void
    {
        $table = self::tableFor($method);
        $routeId = self::routeId($method, $routeKey, $accountId);

        if (self::routeExists($table, $routeId)) {
            return;
        }

        try {
            DB::table($table)->insert([
                'id' => $tlId,
                'route_id' => $routeId,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ]);
        } catch (QueryException $e) {
            // Lost a concurrent mark: fine only if the winner wrote this
            // exact route_id — anything else (missing table, ...) stays loud.
            if (!self::routeExists($table, $routeId)) {
                throw $e;
            }
        }
    }

    /** @phpstan-impure reads live DB state — a prior false must not be remembered */
    private static function routeExists(string $table, string $routeId): bool
    {
        return DB::table($table)->where('route_id', $routeId)->exists();
    }

    /**
     * Deterministic route_id uuid: same (method, key, account) always
     * derives the same value, so the unique index is the dedup gate.
     */
    private static function routeId(string $method, string $routeKey, int $accountId): string
    {
        return (string) Uuid::v5(Uuid::fromString(self::ROUTE_NS), "{$method}|{$accountId}|{$routeKey}");
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    private static function sortedKeys(array $params): array
    {
        $sorted = [];
        foreach ($params as $key => $value) {
            $sorted[(string) $key] = is_array($value) ? self::sortedKeys($value) : $value;
        }
        ksort($sorted, SORT_STRING);

        return $sorted;
    }
}
