<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Ingest;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use MeRezaRezaei\Teleframe\Ingest\Events\UpdateStored;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessage;
use MeRezaRezaei\Teleframe\Mirror\Models\TfMessageService;
use MeRezaRezaei\Teleframe\Mirror\Models\TfUpdate;
use MeRezaRezaei\Teleframe\Schema\Eloquent\PeerShapeTool;
use MeRezaRezaei\Teleframe\Schema\Eloquent\TfMirrorModel;
use Psr\Log\LoggerInterface;
use RuntimeException;

/**
 * Curated-dial ingestor: raw TL update arrays → hand-authored tf_* rows.
 *
 * Phase C re-pointed the write path off the legacy contract (constructor_id
 * FK, tl_data JSONB, extracted legacy columns, generated Tl* models) onto the
 * curated dial the same way the mirror seam decomposes: booleans as live
 * wire-false columns, peer shapes expanded to {field}_type / {field}_id pairs
 * via PeerShapeTool, `constructor` as the ctor name. No tl_data, no
 * constructor_id, no timestamps.
 *
 * Domains decomposed here:
 * - messages — `message` / `messageEmpty` / `messageService` → tf_messages /
 *   tf_messages_service (upsert-stable on (account_id, id)) plus the 1:1 peer
 *   children (from_id, saved_peer_id);
 * - updates — every single `update*` ctor → tf_updates keyed
 *   (account_id, seq, position) with its hand-authored children (row
 *   existence = fact existence); a nested message object lands in the
 *   messages mirror (sibling domain); `updates` / `updatesCombined` /
 *   `updatesTooLong` containers decompose their nested updates;
 * - envelopes carrying a `messages` list (messages.messages & co.)
 *   decompose each nested message and return the first stored root.
 *
 * Any constructor with no curated surface is a clue (logged via the PSR-3
 * seam when one is injected, silent by default) — never a throw.
 *
 * The public face stays: ingest() / ingestBatch() / ingestResponse() /
 * entityMigrationPaths() / migrationPaths() / the UpdateStored event.
 */
final class UpdateIngestor
{
    /** Message ctors the curated messages surface (tf_messages) owns. */
    private const MESSAGE_CTORS = ['message', 'messageEmpty', 'messageService'];

    /** Update container ctors whose `updates` list carries the real updates. */
    private const UPDATE_CONTAINERS = ['updates', 'updatesCombined', 'updatesTooLong'];

    /** tf_messages bits that don't make the wire as explicit booleans. */
    private const TF_MESSAGES_SERVICE = 'tf_messages_service';

    /** @var array<string, string> ctor → service message table. */
    private const MESSAGE_TABLES = [
        'messageService' => self::TF_MESSAGES_SERVICE,
    ];

    /**
     * Booleans per messages surface — mirrors the curated DDL's
     * `BOOLEAN NOT NULL DEFAULT FALSE` columns, wire-false on absence.
     *
     * @var array<string, list<string>>
     */
    private const MESSAGE_BOOLS = [
        'tf_messages' => [
            'out', 'mentioned', 'media_unread', 'silent', 'post',
            'from_scheduled', 'legacy', 'edit_hide', 'pinned',
            'noforwards', 'invert_media', 'offline',
            'video_processing_pending', 'paid_suggested_post_stars',
            'paid_suggested_post_ton',
        ],
        self::TF_MESSAGES_SERVICE => [
            'out', 'mentioned', 'media_unread', 'reactions_are_possible',
            'silent', 'post', 'legacy',
        ],
    ];

    /**
     * 1:1 scalar update children — child table → payload field + leaf column.
     *
     * @var array<string, array{field: string, column: string}>
     */
    private const UPDATE_SCALAR_CHILDREN = [
        'tf_updates_pts' => ['field' => 'pts', 'column' => 'pts'],
        'tf_updates_pts_count' => ['field' => 'pts_count', 'column' => 'pts_count'],
        'tf_updates_qts' => ['field' => 'qts', 'column' => 'qts'],
        'tf_updates_date' => ['field' => 'date', 'column' => 'date'],
        'tf_updates_max_id' => ['field' => 'max_id', 'column' => 'max_id'],
        'tf_updates_still_unread_count' => ['field' => 'still_unread_count', 'column' => 'still_unread_count'],
        'tf_updates_top_msg_id' => ['field' => 'top_msg_id', 'column' => 'top_msg_id'],
        'tf_updates_folder_id' => ['field' => 'folder_id', 'column' => 'folder_id'],
        'tf_updates_channel' => ['field' => 'channel_id', 'column' => 'channel_id'],
        'tf_updates_chat' => ['field' => 'chat_id', 'column' => 'chat_id'],
        'tf_updates_user' => ['field' => 'user_id', 'column' => 'user_id'],
    ];

    /** 1:1 peer-shaped update children — child table → payload field. */
    private const UPDATE_PEER_CHILDREN = [
        'tf_updates_peer' => 'peer_id',
        'tf_updates_from' => 'from_id',
    ];

    public function __construct(
        private readonly ?Dispatcher $events = null,
        private readonly ?LoggerInterface $logger = null,
    ) {}

    /**
     * Migration paths for the hand-authored updates/entity dial.
     *
     * The curated dial itself lives in src/Laravel/Migrations (shipped whole
     * via migrationPaths()); these are the off-dial root-namespace entity
     * anchors the updates/entity mirror writes to — those two files only
     * (the seam test pins the exact list).
     *
     * @return list<string>
     */
    public static function entityMigrationPaths(): array
    {
        return [
            dirname(__DIR__, 2).'/Laravel/Migrations/2026_09_14_200030_create_tf_updates_tables.php',
            dirname(__DIR__, 2).'/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php',
        ];
    }

    /**
     * All migration paths the ingest surface runs: the curated dial dir plus
     * the entity anchors.
     *
     * @return list<string>
     */
    public static function migrationPaths(): array
    {
        return array_merge(
            [dirname(__DIR__, 2).'/Laravel/Migrations'],
            self::entityMigrationPaths(),
        );
    }

    /**
     * Ingest one payload under a tenant; returns the hydrated curated root
     * model (TfMessage / TfUpdate) after the write commits and UpdateStored
     * has fired, or null when the constructor has no curated surface.
     *
     * @param  array<string, mixed>  $payload
     */
    public function ingest(array $payload, int $accountId): ?Model
    {
        $ctor = (string) ($payload['_'] ?? '');
        if ($ctor === '') {
            throw new \InvalidArgumentException(
                "UpdateIngestor: payload carries no '_' constructor node — nothing to ingest",
            );
        }

        $root = DB::transaction(fn (): ?Model => $this->ingestImpl($payload, $accountId, $ctor));
        if ($root !== null) {
            $this->events?->dispatch(new UpdateStored($root, $accountId));
        }

        return $root;
    }

    /**
     * Ingest a batch of payloads under one transaction, one UpdateStored per
     * stored root, in order.
     *
     * @param  iterable<array<string, mixed>>  $payloads
     * @return list<Model>
     */
    public function ingestBatch(iterable $payloads, int $accountId): array
    {
        $roots = [];
        DB::transaction(function () use ($payloads, $accountId, &$roots): void {
            foreach ($payloads as $payload) {
                $ctor = (string) ($payload['_'] ?? '');
                if ($ctor === '') {
                    continue; // batch skip — same clue rule as ingest(), never throws
                }
                $root = $this->ingestImpl((array) $payload, $accountId, $ctor);
                if ($root !== null) {
                    $roots[] = $root;
                }
            }
        });

        foreach ($roots as $root) {
            $this->events?->dispatch(new UpdateStored($root, $accountId));
        }

        return $roots;
    }

    /**
     * Ingest a method RESPONSE under a tenant. The legacy route-table
     * dedup (RouteIdempotency) is gone with the tl_route_* tables; a
     * response is a payload like any other — update-kind payloads and
     * message envelopes decompose into the curated surface.
     *
     * @param  array<string, mixed>  $params
     * @param  array<string, mixed>  $response
     */
    public function ingestResponse(string $method, array $params, array $response, int $accountId): ?Model
    {
        return $this->ingest($response, $accountId);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function ingestImpl(array $payload, int $accountId, string $ctor): ?Model
    {
        if (in_array($ctor, self::MESSAGE_CTORS, true)) {
            return $this->writeMessage($payload, $accountId, $ctor);
        }

        if (in_array($ctor, self::UPDATE_CONTAINERS, true)) {
            return $this->writeUpdateContainer($payload, $accountId);
        }

        if (str_starts_with($ctor, 'update')) {
            return $this->writeUpdate($payload, $accountId, $ctor);
        }

        $messages = $payload['messages'] ?? null;
        if (is_array($messages) && array_is_list($messages)) {
            return $this->writeEnvelope($payload, $accountId);
        }

        $this->clue("UpdateIngestor: constructor '{$ctor}' has no curated mirror surface — nothing stored");

        return null;
    }

    /**
     * Decompose a message ctor into its curated table (upsert-stable on
     * (account_id, id)) with the 1:1 peer children (from_id, saved_peer_id).
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeMessage(array $payload, int $accountId, string $ctor): TfMirrorModel
    {
        $table = self::MESSAGE_TABLES[$ctor] ?? 'tf_messages';
        $modelClass = $table === self::TF_MESSAGES_SERVICE ? TfMessageService::class : TfMessage::class;

        $id = (int) ($payload['id'] ?? 0);
        [$peerType, $peerId] = PeerShapeTool::normalize(self::peerValue($payload['peer_id']));

        $fill = [
            'account_id' => $accountId,
            'id' => $id,
            'constructor' => $ctor,
            'peer_type' => $peerType,
            'peer_id' => $peerId,
            'date' => (int) ($payload['date'] ?? 0),
            'message' => (string) ($payload['message'] ?? ''),
        ];
        foreach (self::MESSAGE_BOOLS[$table] as $bool) {
            $fill[$bool] = (bool) ($payload[$bool] ?? false);
        }

        DB::table($table)->updateOrInsert(['account_id' => $accountId, 'id' => $id], $fill);

        foreach (['from_id', 'saved_peer_id'] as $field) {
            if (! is_array($payload[$field] ?? null)) {
                continue;
            }
            [$type, $peer] = PeerShapeTool::normalize((array) $payload[$field]);
            DB::table("{$table}_{$field}")->updateOrInsert(
                ['account_id' => $accountId, 'id' => $id],
                ['account_id' => $accountId, 'id' => $id, "{$field}_type" => $type, "{$field}_id" => $peer],
            );
        }

        /** @var TfMirrorModel|null $stored */
        $stored = $modelClass::forAccount($accountId)->where('id', $id)->first();
        if ($stored === null) {
            throw new RuntimeException("UpdateIngestor: failed to re-read '{$table}' row ({$accountId}, {$id}) after write");
        }

        return $stored;
    }

    /**
     * Decompose a single update ctor into tf_updates (keyed (account_id,
     * seq, position) — 0 / 0 for a lone update) plus its hand-authored
     * children, and route a nested message object to the messages mirror.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeUpdate(array $payload, int $accountId, string $ctor): Model
    {
        $key = ['account_id' => $accountId, 'seq' => 0, 'position' => 0];

        DB::table('tf_updates')->updateOrInsert($key, [...$key, 'constructor' => $ctor]);
        $this->writeUpdateChildren($payload, $key);

        $nested = $payload['message'] ?? null;
        if (is_array($nested) && in_array((string) ($nested['_'] ?? ''), self::MESSAGE_CTORS, true)) {
            $this->writeMessage($nested, $accountId, (string) $nested['_']);
        }

        $stored = TfUpdate::forAccount($accountId)->where('seq', 0)->where('position', 0)->first();
        if ($stored === null) {
            throw new RuntimeException("UpdateIngestor: failed to re-read 'tf_updates' row ({$accountId}, 0, 0) after write");
        }

        return $stored;
    }

    /**
     * Decompose an updates container envelope into each nested single update.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeUpdateContainer(array $payload, int $accountId): ?Model
    {
        $items = $payload['updates'] ?? null;
        $first = null;
        if (is_array($items) && array_is_list($items)) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $ctor = (string) ($item['_'] ?? '');
                if (str_starts_with($ctor, 'update') && ! in_array($ctor, self::UPDATE_CONTAINERS, true)) {
                    $first ??= $this->writeUpdate($item, $accountId, $ctor);
                }
            }
        }

        if ($first === null) {
            $this->clue('UpdateIngestor: update container carries no single-update members — nothing stored');
        }

        return $first;
    }

    /**
     * Decompose a message-carrier envelope (messages.messages & co.) into
     * each nested message; the first stored message is the returned root.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeEnvelope(array $payload, int $accountId): ?Model
    {
        $messages = $payload['messages'] ?? null;
        if (! is_array($messages) || ! array_is_list($messages)) {
            $this->clue('UpdateIngestor: envelope payload carries no list "messages" member — nothing stored');

            return null;
        }

        $first = null;
        foreach ($messages as $item) {
            if (! is_array($item)) {
                continue;
            }
            $ctor = (string) ($item['_'] ?? '');
            if (in_array($ctor, self::MESSAGE_CTORS, true)) {
                $first ??= $this->writeMessage($item, $accountId, $ctor);
            }
        }

        if ($first === null) {
            $this->clue('UpdateIngestor: envelope carries no message ctor members — nothing stored');
        }

        return $first;
    }

    /**
     * Write the hand-authored tf_updates_* children present in the payload.
     * Row existence = fact existence: a missing field means no child row
     * (the curated 1:1 / 1:N children are presence facts).
     *
     * @param  array<string, mixed>  $payload
     * @param  array{account_id: int, seq: int, position: int}  $key
     */
    private function writeUpdateChildren(array $payload, array $key): void
    {
        foreach (self::UPDATE_SCALAR_CHILDREN as $table => $spec) {
            $value = $payload[$spec['field']] ?? null;
            if ($value === null) {
                continue;
            }
            DB::table($table)->updateOrInsert(
                $key,
                [...$key, $spec['column'] => (int) $value],
            );
        }

        foreach (self::UPDATE_PEER_CHILDREN as $table => $field) {
            if (! is_array($payload[$field] ?? null)) {
                continue;
            }
            [$type, $peer] = PeerShapeTool::normalize((array) $payload[$field]);
            DB::table($table)->updateOrInsert(
                $key,
                [...$key, 'peer_type' => $type, 'peer_id' => $peer],
            );
        }

        $ids = $payload['messages'] ?? null;
        if (is_array($ids)) {
            $position = 0;
            foreach ($ids as $id) {
                DB::table('tf_updates_messages')->updateOrInsert(
                    [...$key, 'message_position' => $position],
                    [...$key, 'message_position' => $position, 'message_id' => (int) $id],
                );
                $position++;
            }
        }

        $nested = $payload['message'] ?? null;
        if (is_array($nested) && isset($nested['_'])) {
            [$type, $peer] = PeerShapeTool::normalize(self::peerValue($nested['peer_id'] ?? null));
            DB::table('tf_updates_message')->updateOrInsert(
                $key,
                [...$key, 'peer_type' => $type, 'peer_id' => $peer, 'message_id' => (int) ($nested['id'] ?? 0)],
            );
        } elseif (is_string($nested) && $nested !== '') {
            [$type, $peer] = PeerShapeTool::normalize(self::peerValue($payload['peer_id'] ?? $payload['from_id'] ?? $payload['chat_id'] ?? null));
            DB::table('tf_updates_message')->updateOrInsert(
                $key,
                [...$key, 'peer_type' => $type, 'peer_id' => $peer, 'message_id' => (int) ($payload['id'] ?? 0)],
            );
        }

        $status = $payload['status'] ?? null;
        if (is_array($status) && isset($status['_'])) {
            DB::table('tf_updates_status')->updateOrInsert(
                $key,
                [...$key,
                    'constructor' => (string) $status['_'],
                    'by_me' => (bool) ($status['by_me'] ?? false),
                    'expires' => (int) ($status['expires'] ?? 0),
                    'was_online' => (int) ($status['was_online'] ?? 0),
                ],
            );
        }

        $action = $payload['action'] ?? null;
        if (is_array($action) && isset($action['_'])) {
            DB::table('tf_updates_action')->updateOrInsert(
                $key,
                [...$key,
                    'constructor' => (string) $action['_'],
                    'progress' => (int) ($action['progress'] ?? 0),
                    'emoticon' => (string) ($action['emoticon'] ?? ''),
                    'msg_id' => (int) ($action['msg_id'] ?? 0),
                    'random_id' => (int) ($action['random_id'] ?? 0),
                ],
            );
        }
    }

    /**
     * A peer payload value, normalized to an array for PeerShapeTool.
     *
     * @return array<string, mixed>
     */
    private static function peerValue(mixed $peer): array
    {
        return is_array($peer) ? $peer : [];
    }

    /** Clue-not-swallow: log the ingest clue through the PSR-3 seam (silent by default). */
    private function clue(string $message): void
    {
        $this->logger?->notice($message);
    }
}
