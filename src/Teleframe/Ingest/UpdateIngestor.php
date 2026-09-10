<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerIdTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TlAnchorModel;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlConstructor;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlParam;
use MeRezaRezaei\Teleframe\Schema\Generator\ModelGenerator;
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;

/**
 * Raw TL update arrays (teleframe truth: snake keys, `_` constructor name,
 * raw flag ints) → P1 anchor/instance/child truth. Tenant-scoped by
 * accountId on every anchor (roadmap tenancy contract).
 *
 * The P1 metamodel (schema/sources/*.tl, loaded via the generator layer)
 * drives everything: constructor → crc32 id, anchor/instance/child model
 * classes (Naming), scalar columns vs ref/vectors. The PayloadWalker (plan
 * Task 2) enumerates nodes parents-before-children; writes run bottom-up so
 * every immediate FK (instance→anchor, child→instance, ref columns hold
 * already-written child PKs) is satisfied without deferrable constraints —
 * sqlite ignores DEFERRABLE anyway.
 *
 * Identity: `id` params (→ tl_id) anchor per (account, telegram id); pure
 * identity refs (peerUser/peerChannel — a lone *_id param) anchor the same
 * way (tl_id-aggregation for referenced entities). Constructors without an
 * identity param (entities, paramless shapes, update roots) aggregate by
 * content: an identical column set under the same tenant reuses the
 * existing row, so re-ingesting a payload is a stable no-op.
 */
final class UpdateIngestor
{
    /**
     * Identity-bearing columns (besides tl_id): a constructor whose whole
     * param set is one of these is a pure identity ref (Peer namespace).
     */
    private const IDENTITY_COLUMNS = ['channel_id', 'chat_id', 'user_id'];

    /** @var array<string, TlConstructor>|null ctor name => metamodel entry */
    private static ?array $constructors = null;

    private const MODELS_NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\\';

    private readonly RouteIdempotency $routes;

    public function __construct(
        ?RouteIdempotency $routes = null,
        private readonly ?Dispatcher $events = null,
        private readonly ?\Closure $now = null,
    ) {
        $this->routes = $routes ?? new RouteIdempotency(now: $now);
    }

    /** Framework-free clock (Laravel now() helper is unavailable in plain PHP). */
    private function now(): \DateTimeImmutable
    {
        return $this->now !== null ? ($this->now)() : new \DateTimeImmutable();
    }

    /**
     * Root-namespace entity anchors are off the shipped dial by design
     * (P1 dial semantics: root namespace stays in the full generated set).
     * Tests and console tooling migrate them alongside the dial. Selection
     * is table-driven off the shipped manifest: table => migration file,
     * so filename drift is impossible.
     *
     * @return list<string>
     */
    public static function entityMigrationPaths(): array
    {
        $root = dirname(__DIR__, 3);
        $manifest = json_decode(
            (string) file_get_contents($root . '/generated/schema-manifest.json'),
            true,
        );
        $tables = is_array($manifest) ? ($manifest['tables'] ?? []) : [];

        // Find any table belonging to each required type's migration file.
        // The anchor table name depends on the first constructor (ksort),
        // so we match by type prefix rather than hardcoding a specific table.
        $seen = [];
        $paths = [];
        foreach (['User', 'Chat', 'ChatPhoto', 'Message', 'MessageEntity', 'MessageMedia', 'Peer', 'Update'] as $type) {
            $prefix = 'tl_' . Naming::snake($type) . '_';
            foreach ($tables as $table => $file) {
                if (str_starts_with($table, $prefix) && !isset($seen[$file])) {
                    $seen[$file] = true;
                    $paths[] = $root . '/generated/migrations/' . $file;
                    break;
                }
            }
        }

        return $paths;
    }

    /**
     * All migration paths the ingest surface runs: the shipped curated dial
     * (same dir the provider's loadMigrationsFrom publishes) plus the
     * off-dial entity anchors. Note `migrate --path` REPLACES registered
     * package paths, so the dial must be passed explicitly.
     *
     * @return list<string>
     */
    public static function migrationPaths(): array
    {
        return array_merge(
            [dirname(__DIR__, 3) . '/migrations'],
            self::entityMigrationPaths(),
        );
    }

    /**
     * The P1 metamodel (cached): combined scheme over the committed
     * schema/sources/*.tl, indexed by constructor name.
     *
     * @return array<string, TlConstructor>
     */
    public static function constructors(): array
    {
        if (self::$constructors === null) {
            $scheme = (new SchemaRegenerator())->loadScheme();
            $index = [];
            foreach ($scheme->types() as $type) {
                foreach ($type->constructors() as $constructor) {
                    $index[$constructor->name] = $constructor;
                }
            }
            self::$constructors = $index;
        }

        return self::$constructors;
    }

    /** @var array<string, bool> table => migrated on this connection (checked once) */
    private static array $tablesReady = [];

    /** @var array<string, list<string>>|null type name => family constructor tables (lazy) */
    private static ?array $familyTables = null;

    private static function constructor(string $name): TlConstructor
    {
        $ctor = self::constructors()[$name] ?? null;
        if ($ctor === null) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: unknown TL constructor '{$name}' (not in the committed scheme sources)",
            );
        }

        return $ctor;
    }

    /**
     * The scheme knows constructors whose tables are off the ingest surface
     * (not shipped on the dial nor in entityMigrationPaths) — fail loudly
     * naming the constructor instead of letting a raw QueryException out.
     */
    private static function assertTableReady(string $table, string $constructor): void
    {
        self::$tablesReady[$table] ??= Schema::hasTable($table);
        if (!self::$tablesReady[$table]) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: table '{$table}' for constructor '{$constructor}' is not migrated — "
                . 'extend the dial or UpdateIngestor::entityMigrationPaths()',
            );
        }
    }

    /**
     * Ingest one payload (flat or arbitrarily nested) under a tenant.
     * Returns the root constructor's instance model after the transaction
     * commits and UpdateStored has fired.
     *
     * @param array<string, mixed> $payload
     */
    public function ingest(array $payload, int $accountId): TlAnchorModel
    {
        $nodes = iterator_to_array(PayloadWalker::walk($payload), false);
        if ($nodes === []) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: payload carries no '_' constructor node — nothing to ingest",
            );
        }

        /** @var array<string, TlAnchorModel> $instances path => written model */
        $instances = [];
        /** @var list<array{class: class-string<TlAnchorModel>, parent_path: string, idx: int, value_path?: string, value?: mixed}> $childRows */
        $childRows = [];

        $root = DB::transaction(function () use ($nodes, $accountId, &$instances, &$childRows): TlAnchorModel {
            // Bottom-up: the walker yields parents before children, so the
            // reversed order writes deepest nodes first — every ref column
            // then holds an already-written child instance PK.
            foreach (array_reverse($nodes) as $node) {
                $instances[$node['path']] = $this->writeNode(
                    $node['constructor'],
                    $node['payload'],
                    $node['path'],
                    $accountId,
                    $instances,
                    $childRows,
                );
            }

            // Vector child rows come after their parent instances exist
            // (immediate FK child.parent_id → instance.id).
            foreach ($childRows as $row) {
                $valuePath = $row['value_path'] ?? null;
                $this->upsertChildRow(
                    $row,
                    (int) $instances[$row['parent_path']]->getKey(),
                    $valuePath !== null ? (int) $instances[$valuePath]->getKey() : null,
                    $accountId,
                );
            }

            return $instances[$nodes[0]['path']]; // walker yields the root first
        });

        $this->events?->dispatch(new UpdateStored($root, $accountId));

        return $root;
    }

    /**
     * Ingest a method RESPONSE under a tenant (plan Task 5 wiring):
     * update-kind payloads branch FIRST and always become instances
     * (updates never touch routes, per P1 design); everything else is
     * route-deduped — seen? return the stored instance : ingest + mark.
     *
     * Methods without a generated route table (generic/vector returns —
     * the generator skips them) ingest unconditionally: dedup applies
     * exactly where a tl_route_<method> table exists (and is migrated).
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $response
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?TlAnchorModel
    {
        if (RouteIdempotency::isUpdatePayload($response)) {
            return $this->ingest($response, $accountId);
        }

        $table = RouteIdempotency::tableFor($method);
        if (!Schema::hasTable($table)) {
            return $this->ingest($response, $accountId);
        }

        $key = RouteIdempotency::keyFor($method, $params);
        $storedId = $this->routes->storedId($method, $key, $accountId);
        if ($storedId !== null) {
            return $this->storedInstance((string) ($response['_'] ?? ''), $storedId);
        }

        $root = $this->ingest($response, $accountId);

        // The route row PK IS the stored instance id, so a response already
        // recorded under another route (content-aggregated roots can be
        // byte-identical across params) must not be re-marked — the unique
        // PK would reject it.
        if (!DB::table($table)->where('id', (int) $root->getKey())->exists()) {
            $this->routes->mark($method, $key, $accountId, (int) $root->getKey());
        }

        return $root;
    }

    /**
     * The instance a seen route points at, resolved through the duplicate
     * response's constructor tables (normally the same constructor that
     * answered first; null if the family drifted and the id misses).
     */
    private function storedInstance(string $constructor, int $storedId): ?TlAnchorModel
    {
        if ($constructor === '') {
            return null;
        }

        /** @var class-string<TlAnchorModel> $instanceClass */
        $instanceClass = self::modelClass(Naming::ctorModel(self::constructor($constructor)->resultType, $constructor));

        /** @var TlAnchorModel|null $instance */
        $instance = $instanceClass::query()->find($storedId);

        return $instance;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, TlAnchorModel> $instances
     * @param list<array{class: class-string<TlAnchorModel>, parent_path: string, idx: int, value_path?: string, value?: mixed}> $childRows
     */
    private function writeNode(
        string $name,
        array $payload,
        string $path,
        int $accountId,
        array $instances,
        array &$childRows,
    ): TlAnchorModel {
        $ctor = self::constructor($name);
        /** @var class-string<TlAnchorModel> $anchorClass */
        $anchorClass = self::modelClass(Naming::model($ctor->resultType));
        /** @var class-string<TlAnchorModel> $instanceClass */
        $instanceClass = self::modelClass(Naming::ctorModel($ctor->resultType, $name));
        self::assertTableReady((new $anchorClass())->getTable(), $name);

        $columns = [];
        $paramByField = [];
        foreach ($ctor->params() as $p) {
            $paramByField[$p->name] = $p;
        }
        foreach ($payload as $key => $value) {
            if ($key === '_') {
                continue;
            }
            if (is_array($value)) {
                if (array_is_list($value)) {
                    $childClass = self::childModelClass($ctor, $name, $key);
                    foreach ($value as $idx => $item) {
                        $row = [
                            'class' => $childClass,
                            'parent_path' => $path,
                            'idx' => (int) $idx,
                        ];
                        if (is_array($item) && isset($item['_'])) {
                            // object element → value_id column (ref-vector table)
                            $row['value_path'] = self::joinPath($path, $key, (int) $idx);
                        } else {
                            // scalar element → value column (scalar-vector table)
                            $row['value'] = $item;
                        }
                        $childRows[] = $row;
                    }
                } elseif (isset($value['_'])) {
                    $param = $paramByField[$key] ?? null;
                    if ($param !== null
                        && $param->kind() === 'ref'
                        && in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
                        // T1.2: Peer/InputPeer refs are canonical longs, not
                        // child-instance PKs. The parent column carries
                        // Telegram's own int64 peer id; the walker's peer
                        // child rows (tl_peer …) still persist alongside.
                        $long = self::peerLong((array) $value);
                        if ($long !== null) {
                            $columns[Naming::column((string) $key)] = $long;
                        }
                        continue;
                    }
                    $child = $instances[self::joinPath($path, $key, null)] ?? null;
                    if ($child !== null) {
                        $columns[Naming::column($key)] = (int) $child->getKey(); // ref column = child instance PK
                    }
                }
                continue;
            }
            $columns[Naming::column((string) $key)] = $value;
        }

        $identity = self::identityColumn($ctor, $columns);

        // P2 M3: serialize identity resolution + anchor upsert per
        // (account, identity class, natural id). Identity lives in the
        // instance tables, so no anchor-side unique constraint can guard
        // this — without the lock, two concurrent workers both miss the
        // existing-anchor lookup and mint duplicates for the same
        // telegram identity. IdentityLock: in-process depth-counted map +
        // pg advisory xact lock on THIS connection inside THIS transaction.
        $lockKey = $identity !== null
            ? 'tl_anchor:' . $accountId . ':' . $identity[0] . ':' . $identity[1]
            : null;
        if ($lockKey !== null) {
            IdentityLock::acquire(DB::connection(), $lockKey);
        }

        try {
            $anchorId = $identity !== null
                ? $this->existingAnchorId($instanceClass, $anchorClass, $ctor->resultType, $identity[0], $identity[1], $accountId)
                : $this->contentAnchorId($instanceClass, $anchorClass, $columns, $accountId);

            if ($anchorId === null) {
                $anchorFill = [
                    'constructor_id' => $ctor->id,
                    'constructor_name' => $name,
                    'account_id' => $accountId,
                ];
                // For global-ID types the anchor and instance share the same
                // table — the anchor row must carry the identity column (tl_id)
                // to satisfy the NOT NULL constraint before the instance fills
                // the remaining columns.  For non-merged types (e.g. Peer)
                // the identity column lives on the instance table only; only
                // include it on the anchor if the anchor table has it.
                if ($identity !== null
                    && Schema::hasColumn((new $anchorClass())->getTable(), $identity[0])) {
                    $anchorFill[$identity[0]] = $identity[1];
                }
                // Global-ID types (id:long) use the Telegram ID as the PK
                // rather than auto-increment — the 'id' column holds the
                // same value as 'tl_id' (dual reference).
                if ($identity !== null && $identity[0] === 'tl_id') {
                    $anchorFill['id'] = $identity[1];
                }
                $anchor = new $anchorClass();
                $anchor->forceFill($anchorFill);
                $anchor->save();

                $anchorId = (int) $anchor->getKey();
            } else {
                // Reused anchor: keep the discriminator truthful when the
                // constructor changed (user → userEmpty transition) — the
                // anchor tells the CURRENT constructor of its instance family.
                $anchorClass::query()->where('id', $anchorId)->where(
                    fn ($q) => $q->where('constructor_id', '!=', $ctor->id)->orWhere('constructor_name', '!=', $name),
                )->update([
                    'constructor_id' => $ctor->id,
                    'constructor_name' => $name,
                    'updated_at' => $this->now(),
                ]);
            }

            $instance = $instanceClass::query()->withoutGlobalScopes()->find($anchorId) ?? new $instanceClass();
            $instance->setAttribute('id', $anchorId); // shared PK with the anchor (spec §4.2)
            $instance->setAttribute('constructor_id', $ctor->id);
            $instance->setAttribute('constructor_name', $name);
            $instance->setAttribute('account_id', $accountId);
            $instance->fill($columns);
            $instance->save();
        } finally {
            if ($lockKey !== null) {
                IdentityLock::release($lockKey);
            }
        }

        return $instance;
    }

    /**
     * Telegram's canonical Peer long for an ingested peer object
     * (T1.2: peerUser→+id, peerChat→-id, peerChannel→-(2^32)-id).
     *
     * @param array<string, mixed> $value
     */
    private static function peerLong(array $value): ?int
    {
        $ctor = (string) ($value['_'] ?? '');
        return match ($ctor) {
            'peerUser' => PeerIdTool::userLong((int) ($value['user_id'] ?? 0)),
            'peerChat' => PeerIdTool::chatLong((int) ($value['chat_id'] ?? 0)),
            'peerChannel' => PeerIdTool::channelLong((int) ($value['channel_id'] ?? 0)),
            default => null,
        };
    }

    /**
     * Identity for anchor reuse: the `id` param (column tl_id), or a lone
     * identity ref param (peerUser/peerChannel/peerChat — their *_id IS the
     * whole constructor). Constructors with any further content (entities,
     * update roots, paramless shapes) return null → content aggregation.
     *
     * @param array<string, mixed> $columns
     * @return array{0: string, 1: int|string}|null
     */
    private static function identityColumn(TlConstructor $ctor, array $columns): ?array
    {
        // Global-ID constructors (id:long): the Telegram ID lives in the
        // reserved-word alias 'tl_id' (Naming::column('id')), separate from
        // the auto-increment PK — enabling multi-tenant anchor reuse.
        foreach ($ctor->params() as $p) {
            if ($p->kind() === 'scalar' && $p->name === 'id' && $p->baseType() === 'long') {
                $tlId = $columns['tl_id'] ?? null;
                if (is_int($tlId) || is_string($tlId)) {
                    return ['tl_id', $tlId];
                }
            }
        }

        $tlId = $columns['tl_id'] ?? null;
        if (is_int($tlId) || is_string($tlId)) {
            return ['tl_id', $tlId];
        }

        $params = array_values(array_filter(
            $ctor->params(),
            static fn (TlParam $p): bool => !$p->isFiller,
        ));
        if (count($params) === 1 && $params[0]->kind() !== 'vector') {
            $column = Naming::column($params[0]->name);
            if (in_array($column, self::IDENTITY_COLUMNS, true) && isset($columns[$column])) {
                return [$column, $columns[$column]];
            }
        }

        return null;
    }

    /**
     * Anchor for (tenant, identity value): identities live on the instance
     * tables (per-constructor), so resolve through them — no global lookups
     * by telegram id alone (roadmap tenancy contract). Constructor
     * transitions (user → userEmpty for the same telegram id) anchor
     * through ANY family instance table carrying the identity, so the
     * namespace keeps exactly one anchor per (tenant, telegram id).
     *
     * @param class-string<TlAnchorModel> $instanceClass
     * @param class-string<TlAnchorModel> $anchorClass
     */
    private function existingAnchorId(string $instanceClass, string $anchorClass, string $type, string $column, int|string $value, int $accountId): ?int
    {
        // Global-ID types (id:long → Telegram ID IS the PK) are shared
        // across all accounts: one row per Telegram entity, no account_id
        // filter.  Scoped types (Message, etc.) are per-tenant: filter by
        // account_id so cross-tenant rows are invisible.
        $isGlobalId = ($column === 'tl_id');

        $query = $instanceClass::query()
            ->where($column, $value);
        if (!$isGlobalId) {
            $query->where('account_id', $accountId);
        }
        $ids = $query->pluck('id')->all();

        if ($ids === []) {
            $ownTable = (new $instanceClass())->getTable();
            foreach (self::familyInstanceTables($type) as $table) {
                if ($table === $ownTable || !Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                    continue;
                }
                $tblQ = DB::table($table)
                    ->where($column, $value);
                if (!$isGlobalId) {
                    $tblQ->where('account_id', $accountId);
                }
                $ids = $tblQ->pluck('id')->all();
                if ($ids !== []) {
                    break;
                }
            }
        }

        if ($ids === []) {
            return null;
        }

        // For global-ID types skip the account_id filter in anchorIdFor —
        // the anchor row is shared, any account can reuse it.
        if ($isGlobalId) {
            /** @var TlAnchorModel|null $anchor */
            $anchor = $anchorClass::query()
                ->whereIn('id', $ids)
                ->first();
            return $anchor !== null ? (int) $anchor->getKey() : null;
        }

        return $this->anchorIdFor($anchorClass, $ids, $accountId);
    }

    /**
     * Constructor tables for a TL type (the constructor family),
     * straight off the metamodel — exact, no table-name guessing.
     *
     * @return list<string>
     */
    private static function familyInstanceTables(string $type): array
    {
        if (self::$familyTables === null) {
            $map = [];
            foreach (self::constructors() as $ctor) {
                $map[$ctor->resultType][] = Naming::constructorTable($ctor->resultType, $ctor->name);
            }
            self::$familyTables = $map;
        }

        return self::$familyTables[$type] ?? [];
    }

    /**
     * Content aggregation for identity-less constructors (entities,
     * paramless shapes, update roots): an instance of the same tenant whose
     * verbatim column set matches is reused — re-ingesting an identical
     * payload touches nothing.
     *
     * @param array<string, mixed> $columns
     * @param class-string<TlAnchorModel> $instanceClass
     * @param class-string<TlAnchorModel> $anchorClass
     */
    private function contentAnchorId(string $instanceClass, string $anchorClass, array $columns, int $accountId): ?int
    {
        $query = $instanceClass::query();
        foreach ($columns as $column => $value) {
            $query->where($column, $value);
        }
        $ids = $query->pluck('id')->all();

        return $this->anchorIdFor($anchorClass, $ids, $accountId);
    }

    /**
     * @param class-string<TlAnchorModel> $anchorClass
     * @param list<int|string> $ids
     */
    private function anchorIdFor(string $anchorClass, array $ids, int $accountId): ?int
    {
        if ($ids === []) {
            return null;
        }

        /** @var TlAnchorModel|null $anchor */
        $anchor = $anchorClass::query()
            ->where('account_id', $accountId)
            ->whereIn('id', $ids)
            ->first();

        return $anchor !== null ? (int) $anchor->getKey() : null;
    }

    /**
     * Vector child row upsert: (parent_id, idx) is the stable slot (unique
     * index); object elements link value_id to their instance, scalar
     * elements land in the child `value` column (the two column sets are
     * disjoint per generated DDL — ref-vector vs scalar-vector tables).
     *
     * @param array{class: class-string<TlAnchorModel>, parent_path: string, idx: int, value_path?: string, value?: mixed} $row
     */
    private function upsertChildRow(array $row, int $parentId, ?int $valueId, int $accountId): void
    {
        /** @var class-string<TlAnchorModel> $class */
        $class = $row['class'];
        $isScalar = array_key_exists('value', $row);
        self::assertTableReady((new $class())->getTable(), 'vector child rows');

        $fill = ['parent_id' => $parentId, 'idx' => $row['idx'], 'account_id' => $accountId];
        if ($isScalar) {
            $fill['value'] = $row['value'];
        } else {
            $fill['value_id'] = $valueId;
        }

        $existing = $class::query()
            ->where('parent_id', $parentId)
            ->where('idx', $row['idx'])
            ->where('account_id', $accountId)
            ->first();
        if ($existing !== null) {
            $changed = $isScalar
                ? $existing->getAttribute('value') !== $row['value']
                : (int) $existing->getAttribute('value_id') !== (int) $valueId;
            if ($changed) {
                $existing->forceFill($fill)->save();
            }

            return;
        }

        $new = new $class();
        $new->forceFill($fill);
        $new->save(); // Auto-increment PK
    }

    /** @return class-string<TlAnchorModel> */
    private static function childModelClass(TlConstructor $ctor, string $name, string $param): string
    {
        $instanceTable = Naming::constructorTable($ctor->resultType, $name);

        return self::modelClass(ModelGenerator::childModelClass($instanceTable, $param));
    }

    /** @param string $candidate short or absolute generated model class name
     * @return class-string<TlAnchorModel>
     */
    private static function modelClass(string $candidate): string
    {
        $fqcn = str_starts_with($candidate, '\\') ? $candidate : self::MODELS_NS . $candidate;
        if (!class_exists($fqcn)) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: generated model '{$fqcn}' is missing — run artisan teleframe:regenerate",
            );
        }

        return $fqcn;
    }

    private static function joinPath(string $parentPath, string $param, ?int $vectorIndex): string
    {
        $segment = $vectorIndex !== null ? $param . '.' . $vectorIndex : $param;

        return $parentPath === '' ? $segment : $parentPath . '.' . $segment;
    }
}
