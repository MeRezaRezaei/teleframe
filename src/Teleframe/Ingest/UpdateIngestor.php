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
use MeRezaRezaei\Teleframe\Schema\Generator\Naming;
use MeRezaRezaei\Teleframe\Schema\Generator\SchemaRegenerator;

/**
 * TDLib-style domain table ingestor: raw TL update arrays → domain table
 * rows with extracted query columns + tl_data JSONB.
 *
 * Each TL constructor is classified into a domain (users, messages, etc.)
 * via Naming::classifyType(). The full TL payload is serialized to JSONB,
 * hot-path query columns are extracted, and the row is upserted into the
 * appropriate domain table.
 */
final class UpdateIngestor
{

    /**
     * Domain-specific query column extraction: domain => list of param names to extract.
     *
     * @var array<string, list<string>>
     */
    private const DOMAIN_EXTRACT_COLUMNS = [
        'users' => ['access_hash', 'first_name', 'last_name', 'username', 'phone', 'is_bot', 'is_self', 'is_contact', 'is_premium', 'is_deleted', 'photo_id', 'status_type'],
        'chats' => ['title', 'participants_count', 'version', 'date', 'is_deactivated', 'is_left'],
        'channels' => ['access_hash', 'title', 'username', 'date', 'participants_count', 'is_broadcast', 'is_megagroup', 'is_verified', 'is_restricted', 'is_left', 'is_forum', 'restriction_reason'],
        'messages' => ['message_id', 'peer_id', 'from_id', 'date', 'is_out', 'is_mentioned', 'is_silent', 'is_pinned', 'message_text', 'media_type', 'reply_to_msg_id'],
        'dialogs' => ['peer_id', 'peer_type', 'top_message_id', 'unread_count', 'unread_mentions', 'is_pinned', 'folder_id', 'pts'],
        'updates' => ['peer_id', 'message_id', 'user_id', 'pts', 'pts_count', 'date'],
        'documents' => ['access_hash', 'date', 'mime_type', 'size', 'dc_id', 'file_reference'],
        'photos' => ['access_hash', 'date', 'dc_id', 'has_stickers', 'file_reference'],
        'sticker_sets' => ['access_hash', 'title', 'short_name', 'count', 'hashes'],
        'stories' => ['story_id', 'peer_id', 'date', 'expire_date', 'caption'],
        'wallpapers' => ['access_hash', 'title', 'slug', 'document_id'],
        'channel_participants' => ['channel_id', 'user_id', 'date'],
    ];

    /**
     * TL param name aliases for extraction: domain column key => TL param
     * names to try when the key itself is absent from the payload (TL
     * wire names differ from domain column names: id→message_id,
     * message→message_text). The `is_*` boolean columns fall back to
     * their bare TL flag name (is_out→out) via the generic rule below.
     *
     * @var array<string, list<string>>
     */
    private const EXTRACT_PARAM_ALIASES = [
        'message_id'   => ['id'],
        'story_id'     => ['id'],
        'message_text' => ['message'],
        // Users domain: TL flag-only bools (self/contact/deleted/bot/...)
        // arrive as bare keys, while domain columns are is_*-prefixed.
        'is_bot'       => ['bot'],
        'is_self'      => ['self'],
        'is_contact'   => ['contact'],
        'is_premium'   => ['premium'],
        'is_deleted'   => ['deleted'],
        // Channels domain: same bare-key convention for TL flag bools.
        'is_broadcast' => ['broadcast'],
        'is_megagroup' => ['megagroup'],
        'is_verified'  => ['verified'],
        'is_restricted' => ['restricted'],
        'is_left'      => ['left'],
        'is_forum'     => ['forum'],
    ];

    /**
     * Conflict target for upsert per domain.
     *
     * @var array<string, list<string>>
     */
    private const DOMAIN_CONFLICT_TARGETS = [
        'users'                => ['id', 'account_id'],
        'chats'                => ['id', 'account_id'],
        'channels'             => ['id', 'account_id'],
        'messages'             => ['peer_id', 'message_id', 'account_id'],
        'dialogs'              => ['peer_id', 'account_id'],
        'documents'            => ['id', 'account_id'],
        'photos'               => ['id', 'account_id'],
        'sticker_sets'         => ['id', 'account_id'],
        'stories'              => ['peer_id', 'story_id', 'account_id'],
        'wallpapers'           => ['id', 'account_id'],
        'channel_participants' => ['channel_id', 'user_id', 'account_id'],
    ];

    /** @var array<string, TlConstructor>|null ctor name => metamodel entry */
    private static ?array $constructors = null;

    /** @var array<string, string>|null domain => table name (lazy) */
    private static ?array $domainTables = null;

    private const MODELS_NS = 'MeRezaRezaei\Teleframe\Schema\Generated\Models\\';

    private readonly RouteIdempotency $routes;

    public function __construct(
        ?RouteIdempotency $routes = null,
        private readonly ?Dispatcher $events = null,
        private readonly ?\Closure $now = null,
    ) {
        $this->routes = $routes ?? new RouteIdempotency(now: $now);
    }

    /**
     * Migration paths for the ingest surface: shipped domain table migrations.
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

        $paths = [];
        foreach ($tables as $table => $file) {
            if (str_starts_with($table, 'tf_') && !str_starts_with($table, 'tl_route_')) {
                $paths[] = $root . '/generated/migrations/' . $file;
            }
        }
        sort($paths);
        return array_values(array_unique($paths));
    }

    /**
     * All migration paths the ingest surface runs.
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

    private static function assertTableReady(string $table, string $context): void
    {
        self::$tablesReady[$table] ??= Schema::hasTable($table);
        if (!self::$tablesReady[$table]) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: table '{$table}' ({$context}) is not migrated",
            );
        }
    }

    /**
     * Ingest one payload (flat or arbitrarily nested) under a tenant.
     * Returns the root constructor's domain model after the transaction
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

        /** @var array<string, TlAnchorModel> $written path => written model */
        $written = [];

        $root = DB::transaction(function () use ($nodes, $accountId, &$written): TlAnchorModel {
            foreach (array_reverse($nodes) as $node) {
                $model = $this->writeNode(
                    $node['constructor'],
                    $node['payload'],
                    $accountId,
                );
                if ($model !== null) {
                    $written[$node['path']] = $model;
                }
            }

            if (isset($written[$nodes[0]['path']])) {
                return $written[$nodes[0]['path']]; // walker yields the root first
            }

            // The root node is an ephemeral envelope (e.g. messages.Messages):
            // return the first persisted descendant instead.
            $first = reset($written);
            if ($first === false) {
                throw new \InvalidArgumentException(
                    'UpdateIngestor: payload carries no persistable domain node — nothing to ingest',
                );
            }

            return $first;
        });

        $this->events?->dispatch(new UpdateStored($root, $accountId));

        return $root;
    }

    /**
     * Ingest a method RESPONSE under a tenant.
     *
     * @param array<string, mixed> $params
     * @param array<string, mixed> $response
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): TlAnchorModel
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
            // Envelope ctors (messages.messages, users.users, ...) persist
            // only their descendants, so no stored instance resolves — fall
            // back to an idempotent re-ingest (upsert-stable) instead of
            // returning null for a seen route.
            return $this->storedInstance((string) ($response['_'] ?? ''), $storedId)
                ?? $this->ingest($response, $accountId);
        }

        $root = $this->ingest($response, $accountId);

        // Surrogate-key roots (messages, updates: null key on sqlite) have
        // nothing stable to point the route at — marking id 0 would collide
        // across distinct keys, so only native-id roots mark routes.
        if ($root->getKey() !== null && !DB::table($table)->where('id', (int) $root->getKey())->exists()) {
            $this->routes->mark($method, $key, $accountId, (int) $root->getKey());
        }

        return $root;
    }

    /**
     * The instance a seen route points at, resolved through the domain table.
     */
    private function storedInstance(string $constructor, int $storedId): ?TlAnchorModel
    {
        if ($constructor === '') {
            return null;
        }

        $ctor = self::constructor($constructor);
        $classification = Naming::classifyConstructor($constructor, $ctor->resultType);
        if ($classification === null) {
            return null;
        }

        $domain = $classification['domain'];
        $modelClass = self::domainModelClass($domain);

        /** @var TlAnchorModel|null $instance */
        $instance = $modelClass::query()->find($storedId);

        return $instance;
    }

    /**
     * Write a single TL node to its domain table.
     *
     * @param array<string, mixed> $payload
     */
    private function writeNode(
        string $name,
        array $payload,
        int $accountId,
    ): ?TlAnchorModel {
        $ctor = self::constructor($name);
        $classification = Naming::classifyConstructor($name, $ctor->resultType);
        if ($classification === null) {
            // Ephemeral envelope (e.g. messages.Messages): its children were
            // walked and persisted as domain rows; nothing to store for the
            // envelope itself.
            return null;
        }

        $domain = $classification['domain'];
        $table = self::domainTable($domain);
        self::assertTableReady($table, $domain);
        $modelClass = self::domainModelClass($domain);

        // Build the full TL data as JSONB
        $tlData = $this->serializeToTlData($payload);

        // Extract query columns from the payload
        $extractCols = self::DOMAIN_EXTRACT_COLUMNS[$domain] ?? [];
        $columns = $this->extractColumns($ctor, $payload, $extractCols);

        // Compute conflict target for upsert
        $conflictCols = self::DOMAIN_CONFLICT_TARGETS[$domain] ?? ['id', 'account_id'];

        // Build the fill array
        $fill = array_merge([
            'constructor_id' => $ctor->id,
            'account_id' => $accountId,
            'tl_data' => $tlData,
        ], $columns);

        // For global-ID types, set the id column from the payload
        $idValue = $this->extractGlobalId($ctor, $columns, $payload);
        if ($idValue !== null) {
            $fill['id'] = $idValue;
        }

        // Upsert
        $existing = $this->findExisting($modelClass, $conflictCols, $fill);
        if ($existing !== null) {
            $existing->forceFill($fill);
            $existing->save();

            return $existing;
        }

        $model = new $modelClass();
        $model->forceFill($fill);
        $model->save();

        return $model;
    }

    /**
     * Serialize a TL payload to the tl_data JSONB structure.
     *
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    private function serializeToTlData(array $payload): array
    {
        $data = [];
        foreach ($payload as $key => $value) {
            if ($key === '_') {
                $data['_'] = $value;
            } else {
                // Store all fields as-is: scalars, nested objects, and vectors.
                // Nested objects that were also written as separate domain rows
                // keep their full representation in the parent's JSONB for
                // read-path convenience; the domain row is the write-side truth.
                $data[$key] = $value;
            }
        }
        return $data;
    }

    /**
     * Extract query columns from a TL payload for a given domain.
     *
     * @param array<string, mixed> $payload
     * @param list<string> $extractCols
     *
     * @return array<string, mixed>
     */
    private function extractColumns(TlConstructor $ctor, array $payload, array $extractCols): array
    {
        $columns = [];
        $paramByField = [];
        foreach ($ctor->params() as $p) {
            $paramByField[$p->name] = $p;
        }

        foreach ($extractCols as $colKey) {
            $col = Naming::column($colKey);
            $tlName = $colKey;
            $value = $payload[$colKey] ?? null;

            if ($value === null) {
                foreach (self::EXTRACT_PARAM_ALIASES[$colKey] ?? [] as $alias) {
                    if (array_key_exists($alias, $payload) && $payload[$alias] !== null) {
                        $tlName = $alias;
                        $value = $payload[$alias];
                        break;
                    }
                }
            }

            if ($value === null && str_starts_with($colKey, 'is_')) {
                $bare = substr($colKey, 3);
                if (array_key_exists($bare, $payload) && $payload[$bare] !== null) {
                    $tlName = $bare;
                    $value = $payload[$bare];
                }
            }

            if ($value === null) {
                continue;
            }

            $param = $paramByField[$tlName] ?? $paramByField[$colKey] ?? null;
            if ($param === null) {
                continue;
            }

            if (is_array($value) && isset($value['_'])) {
                // Nested object — extract peer long for Peer/InputPeer params
                if ($param->kind() === 'ref' && in_array($param->baseType(), ['Peer', 'InputPeer'], true)) {
                    $long = self::peerLong($value);
                    if ($long !== null) {
                        $columns[$col] = $long;
                    }
                }
                // For other object params, store null (the tl_data has the full object)
            } elseif (is_array($value) && array_is_list($value)) {
                // Vector — skip (stored in tl_data)
            } else {
                $columns[$col] = $value;
            }
        }

        // Special handling for peer_id: extract from Peer object if present
        if (in_array('peer_id', $extractCols, true) && !isset($columns['peer_id'])) {
            $peerParam = $paramByField['peer_id'] ?? null;
            if ($peerParam !== null && is_array($payload['peer_id'] ?? null)) {
                $long = self::peerLong((array) $payload['peer_id']);
                if ($long !== null) {
                    $columns['peer_id'] = $long;
                }
            }
        }

        return $columns;
    }

    /**
     * Extract the global ID (Telegram native ID) from the payload.
     * Returns null for scoped-ID types.
     */
    private function extractGlobalId(TlConstructor $ctor, array $columns, array $payload): ?int
    {
        foreach ($ctor->params() as $p) {
            if ($p->kind() === 'scalar' && $p->name === 'id' && $p->baseType() === 'long') {
                $val = $columns['id'] ?? $payload['id'] ?? null;
                return $val !== null ? (int) $val : null;
            }
        }
        return null;
    }

    /**
     * Find an existing row for upsert conflict resolution.
     *
     * @param class-string<TlAnchorModel> $modelClass
     * @param list<string> $conflictCols
     * @param array<string, mixed> $fill
     */
    private function findExisting(string $modelClass, array $conflictCols, array $fill): ?TlAnchorModel
    {
        $query = $modelClass::query()->withoutGlobalScopes();
        foreach ($conflictCols as $col) {
            $val = $fill[$col] ?? null;
            if ($val === null) {
                return null; // can't match without all conflict columns
            }
            $query->where($col, $val);
        }

        /** @var TlAnchorModel|null $existing */
        $existing = $query->first();

        return $existing;
    }

    /**
     * Telegram's canonical Peer long for an ingested peer object.
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
     * Resolve the walker path for a child node.
     */

    /**
     * Get the domain table name for a domain.
     */
    private static function domainTable(string $domain): string
    {
        if (self::$domainTables === null) {
            self::$domainTables = [];
            foreach (array_keys(self::DOMAIN_EXTRACT_COLUMNS) as $d) {
                self::$domainTables[$d] = Naming::domainTable($d);
            }
        }

        return self::$domainTables[$domain] ?? Naming::domainTable($domain);
    }

    /**
     * Get the model class for a domain.
     *
     * @return class-string<TlAnchorModel>
     */
    private static function domainModelClass(string $domain): string
    {
        $class = Naming::domainModel($domain);
        $fqcn = self::MODELS_NS . $class;
        if (!class_exists($fqcn)) {
            throw new \InvalidArgumentException(
                "UpdateIngestor: generated model '{$fqcn}' is missing — run artisan teleframe:regenerate",
            );
        }

        return $fqcn;
    }
}
