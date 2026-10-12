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
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;
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
 *   tf_messages_service (upsert-stable on (account_id, id)), and the CHILD
 *   tables come from `MirrorFactDecomposer` (see writeChildrenFromCatalog);
 * - updates — every single `update*` ctor → tf_updates keyed
 *   (account_id, seq, position) with its hand-authored children (row
 *   existence = fact existence); a nested message object lands in the
 *   messages mirror (sibling domain); `updates` / `updatesCombined` /
 *   `updatesTooLong` containers decompose their nested updates;
 * - envelopes carrying a `messages` list (messages.messages & co.)
 *   decompose each nested message and return the first stored root.
 *
 * ## WHY THE CHILD WALK IS THE CATALOG'S, NOT A TWO-FIELD LOOP
 *
 * Measured, not assumed (Cycle 38 — Goal B). This class used to write exactly
 * three tables per message: `foreach (['from_id','saved_peer_id'] as $field)`
 * at the old line 304. `mirror-catalog.json` declares 29 direct children for
 * `tf_messages`, and the real decomposer emits 7 for an ordinary rich message
 * (tf_messages + from_id + fwd_from + reply_to + entities + views + forwards).
 * So `teleframe:backfill` and the public `Teleclient::ingest` /
 * `Teleframe::ingest` API stored a message BODY AND NOTHING ELSE THAT IS A
 * MESSAGE — no reply graph, no forward header, no counters, no media, no
 * entities, no reactions — while landing in the SAME tables as the live bus
 * with DIFFERENT contents for the same message. Identical schemas, so no host
 * could tell a backfilled row from a live one. Cycle 36 already fixed one
 * field on the mirror side alone (commit 3a1f4e50), which is what turned this
 * from a known gap into a shipped wrong fact.
 *
 * THE ALTERNATIVE WAS MEASURED AND REJECTED: routing these callers through
 * `MirrorIngesterFactory::build()` instead (i.e. make backfill and the public
 * API emit update envelopes and let the mirror seam decompose them) does not
 * work, for three independent reasons, all verified against the real classes:
 *
 *  1. `MirrorCatalog::tableForCtor('messages.messages')` returns NULL. The
 *     backfill envelope is not a ctor the catalog knows, and that factory
 *     closure early-returns on a null entry — so the naive swap stores ZERO
 *     tables, turning a 3-table write into a 0-table one. The factory would
 *     first have to grow a `messages.messages` fan-out, which is new
 *     behaviour in a file whose job is single-update routing.
 *  2. That closure is `callable(array, int): void`. `BackfillCommand`
 *     (src/Laravel/Console/BackfillCommand.php:246) needs
 *     `array{stored: int, root: ...}` and calls `$root->getKey()` on the
 *     result; `void` cannot be reported against without inventing a second
 *     return contract.
 *  3. `SelfOriginatedClassifier` SUPPRESSES `UpdateStored` for
 *     self-originated messages. Backfill is by definition the account's own
 *     history, so routing it there would silently stop emitting the event for
 *     exactly the messages backfill exists to fetch.
 *
 * So the duplication is removed on THIS side instead: the 2-field loop became a
 * catalog-driven child walk. `writeMessage` remains the single funnel for both
 * bare message ctors and `messages.*` envelopes (writeEnvelope calls it), so
 * all three shipped entry points — backfill, `Teleclient`, `Teleframe` — pick
 * the fix up at once, and there is no second child set left to drift. The
 * parent row is still written here: this class owns the curated column fill,
 * the MESSAGE_BOOLS dial and the search index, and the decomposer is asked for
 * CHILDREN ONLY, so no writer is duplicated.
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

    /**
     * RPC-RESULT ctors that construct no `Update` and carry none, so the mirror
     * owes no `tf_updates` row for them.
     *
     * `updates.state` is a pts/qts/date/seq snapshot; `updates.differenceEmpty`,
     * `updates.differenceTooLong`, `updates.channelDifferenceEmpty` and
     * `updates.channelDifferenceTooLong` are the absence of a difference or a
     * refusal of the window. Each is stored where its own facts live —
     * `tf_update_state`, `tf_update_differences*`, `tf_channel_updates*`, all
     * hand-authored in the updates dial and all covered by
     * `tests/Mirror/UpdatesMirrorTest.php` — which is why routing them through
     * `writeUpdate()` produced a row in the UPDATE fact log carrying a
     * constructor that is not an update. That is not a row per update; it is a
     * fact with no update in it.
     *
     * This list is the routing counterpart of
     * `EndpointCoverage::KIND_RPC_RESULT`, and the two MUST agree: the
     * classification says what owes a row, this says what must not fake one.
     * `tests/Ingest/RpcResultsPlaceNoFakeUpdateRowTest.php` asserts the two
     * are the same set, read from both sides.
     */
    /**
     * RPC results that carry NO update and no update watermark, by the SAME
     * two-sided test `EndpointCoverage::carriesNoUpdateAtAll()` applies: no
     * `Vector<Update>` field, no singular `Update` ref, and no `pts_count`.
     *
     * The second half is what keeps the four short-poll forms out of this list.
     * `updateShortMessage`, `updateShortChatMessage` and `updateShortSentMessage`
     * declare no `Update` member at all, and `updateShort` declares a singular
     * one — yet all four ARE update endpoints: the three message forms declare
     * `pts` AND `pts_count`, the update-watermark pair no state or error result
     * carries. Routing them here would have stopped placing a `tf_updates` row
     * that is placed today, which is a silent weakening and not a correction.
     *
     * MEASURED on this tree, reading `TlConstructor::allParams()` for every
     * `update*` constructor whose result type is not `Update`:
     *
     * ```
     * updateShort              res=Updates                vecUpdate=. refUpdate=Y pts_count=.
     * updateShortChatMessage   res=Updates                vecUpdate=. refUpdate=. pts_count=Y
     * updateShortMessage       res=Updates                vecUpdate=. refUpdate=. pts_count=Y
     * updateShortSentMessage   res=Updates                vecUpdate=. refUpdate=. pts_count=Y
     * updates                  res=Updates                vecUpdate=Y refUpdate=. pts_count=.
     * updates.channelDifference res=updates.ChannelDifference vecUpdate=Y refUpdate=. pts_count=.
     * updates.channelDifferenceEmpty res=updates.ChannelDifference vecUpdate=. refUpdate=. pts_count=.
     * updates.channelDifferenceTooLong res=updates.ChannelDifference vecUpdate=. refUpdate=. pts_count=.
     * updates.difference       res=updates.Difference     vecUpdate=Y refUpdate=. pts_count=.
     * updates.differenceEmpty  res=updates.Difference     vecUpdate=. refUpdate=. pts_count=.
     * updates.differenceSlice  res=updates.Difference     vecUpdate=Y refUpdate=. pts_count=.
     * updates.differenceTooLong res=updates.Difference    vecUpdate=. refUpdate=. pts_count=.
     * updates.state            res=updates.State          vecUpdate=. refUpdate=. pts_count=.
     * updatesCombined          res=Updates                vecUpdate=Y refUpdate=. pts_count=.
     * updatesTooLong           res=Updates                vecUpdate=. refUpdate=. pts_count=.
     * ```
     *
     * Six rows carry nothing: the six in `RPC_RESULTS` above.
     */
    private const RPC_RESULT_CTORS = [
        'updates.state',
        'updates.differenceEmpty',
        'updates.differenceTooLong',
        'updates.channelDifferenceEmpty',
        'updates.channelDifferenceTooLong',
        'updatesTooLong',
    ];

    /**
     * Every hand-authored tf_updates_* child table — the full set migrated by
     * 2026_09_14_200030_create_tf_updates_tables.php. Children are 1:1 / 1:N
     * presence facts keyed by the parent's (account_id, seq, position); the
     * ingestor purges them per key so a new ctor never inherits stale facts.
     *
     * @var list<string>
     */
    private const UPDATE_CHILD_TABLES = [
        'tf_updates_pts',
        'tf_updates_pts_count',
        'tf_updates_qts',
        'tf_updates_date',
        'tf_updates_message',
        'tf_updates_messages',
        'tf_updates_peer',
        'tf_updates_from',
        'tf_updates_max_id',
        'tf_updates_still_unread_count',
        'tf_updates_top_msg_id',
        'tf_updates_folder_id',
        'tf_updates_status',
        'tf_updates_action',
    ];

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
     * The catalog-derived update-CARRIER map: ctor → the `Vector<Update>` field
     * it carries. Built once per process from the parsed layer-227 scheme.
     *
     * ## WHY IT IS DERIVED AND NOT THE THREE-NAME LIST IT REPLACED
     *
     * This was `private const UPDATE_CONTAINERS = ['updates', 'updatesCombined',
     * 'updatesTooLong']`, and that list was WRONG in the direction that loses
     * data: it named only the two ctors whose vector is called `updates`, and
     * missed the whole difference family, whose vector is called
     * `other_updates`. Measured on this tree:
     *
     * | ctor                            | result type                 | Vector<Update> field |
     * |---------------------------------|-----------------------------|---------------------|
     * | `updates`                       | `Updates`                   | `updates`           |
     * | `updatesCombined`               | `Updates`                   | `updates`           |
     * | `updates.difference`            | `updates.Difference`        | `other_updates`     |
     * | `updates.differenceSlice`       | `updates.Difference`        | `other_updates`     |
     * | `updates.channelDifference`     | `updates.ChannelDifference` | `other_updates`     |
     * | `updatesTooLong`                | `Updates`                   | — (no fields at all) |
     *
     * So `updates.difference`, `updates.differenceSlice` and
     * `updates.channelDifference` — the exact replies to `updates.getDifference`
     * and `updates.getChannelDifference`, which the operator's criterion names
     * as the "requesting for live missing things" call — fell through to
     * `writeUpdate()` and were stored as ONE update row named after the
     * CONTAINER, their `other_updates` vector never walked. MEASURED before the
     * fix, by ingesting each fixture and reading `tf_updates` back: `updates` →
     * `updateChannelTooLong@515/0` (correctly unwrapped), but
     * `updates.difference` → a single `updates.difference@0/0` row and nothing
     * else — the `updateChannel` its `other_updates` carried was DROPPED.
     *
     * So a transcribed field name is worse than a missing one: it cannot be
     * right for all of them, because two of them name the same field and three
     * name a different one. The map is walked out of `TlConstructor::allParams()`
     * instead, keyed on `kind() === 'vector' && baseType() === 'Update'`, and it
     * picks up a constructor the moment the scheme pipeline adds one.
     *
     * `updatesTooLong#e317af7e = Updates;` declares NO field, so it is simply
     * absent from this map — which is the honest routing: there is nothing to
     * unwrap. It is not special-cased to produce a clue; see ingestImpl().
     *
     * @var array<string,string>|null
     */
    private static ?array $updateCarriers = null;

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
    ];

    /**
     * Update scalars the fold moved ONTO `tf_updates` as real columns.
     *
     * `channel_id` / `chat_id` / `user_id` were 1:1 child TABLES holding a
     * single long where the wire declares a single long — the same fabrication
     * `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact`
     * closed. They are parent COLUMNS now, so they are written with the parent
     * row and must NOT appear in UPDATE_CHILD_TABLES or UPDATE_SCALAR_CHILDREN:
     * a name in either list is deleted from and inserted into as a TABLE.
     *
     * @var list<string> payload field, in the same order the fold declares them
     */
    private const UPDATE_FOLDED_SCALARS = ['channel_id', 'chat_id', 'user_id'];

    /** 1:1 peer-shaped update children — child table → payload field. */
    private const UPDATE_PEER_CHILDREN = [
        'tf_updates_peer' => 'peer_id',
        'tf_updates_from' => 'from_id',
    ];

    /**
     * The catalog-derived child decomposer, memoised per process.
     *
     * `TlParser::parseFile()` re-reads and re-parses the entire layer-227
     * scheme, so a per-message rebuild would make backfill quadratic in the
     * size of that file for no benefit — the artifacts are immutable within a
     * process. See decomposer() for the build.
     */
    private static ?MirrorFactDecomposer $decomposer = null;

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
     * ## WHY AN ANCHOR ALREADY INSIDE THE DIAL DIRECTORY IS NOT LISTED TWICE
     *
     * MEASURED 2026-10-05 on this tree: `src/Laravel/Migrations` holds 42
     * `*.php` files (`ls src/Laravel/Migrations/*.php | wc -l`), and BOTH
     * files returned by entityMigrationPaths() —
     * 2026_09_14_200030_create_tf_updates_tables.php and
     * 2026_09_14_202031_create_tf_channel_participants_tables.php — already
     * live inside it. `array_merge([$dir], entityMigrationPaths())` therefore
     * handed every consumer a list in which those two migrations appear
     * twice.
     *
     * Laravel's own migrator hides that: `Migrator::getMigrationFiles()`
     * `keyBy`s the file list by migration name, so the duplicate collapses
     * silently. Every consumer that iterates the list ITSELF
     * (tests/MySql/MySqlTestCase::migratedMysqlConnection(),
     * tests/Backfill/*Test, tests/Concerns/RunsMysqlMigrations) does not, so it
     * applied both a second time and MySQL answered `1050 Table already
     * exists`. SQLite absorbs a repeated `CREATE TABLE IF NOT EXISTS`, which
     * is why the whole defect was invisible until a real MySQL lane existed.
     *
     * So the list is de-duplicated HERE, at the one place every consumer
     * reads: an anchor is carried only when it is not reachable through the
     * dial directory. The check is computed, never hardcoded — moving an
     * anchor back out of the dial directory makes it reappear on the next
     * call, with no edit here.
     *
     * WHAT THIS DOES NOT CHANGE: the SET of migrations that run. Every one of
     * the 42 is still applied exactly once, and the Postgres track consumes
     * this same method. The order is also unchanged in the case that matters:
     * the dial directory is still element 0, and the anchors — when any is
     * genuinely off-dial — still follow it, filename-sorted.
     *
     * The mixed shape (element 0 a DIRECTORY, later elements FILE paths) is
     * preserved on purpose: tests/Ingest/UpdateIngestorTest asserts element 0
     * with `assertDirectoryExists()` and every remaining element with
     * `assertFileExists()`, and each consumer distinguishes the two shapes
     * itself (is_dir). Changing the shape would break them all; changing only
     * the membership breaks none.
     *
     * @return list<string>
     */
    public static function migrationPaths(): array
    {
        $dial = dirname(__DIR__, 2).'/Laravel/Migrations';

        $offDial = [];
        foreach (self::entityMigrationPaths() as $anchor) {
            if (! self::isInside($anchor, $dial)) {
                $offDial[] = $anchor;
            }
        }

        sort($offDial);

        // The D-018 fix. An armed bite-probe was found here on 2026-10-05,
        // left behind by a lane killed mid-proof: it returned
        // `array_merge([$dial], self::entityMigrationPaths(), $offDial)`, which
        // lists both entity anchors TWICE because both already live inside the
        // dial. SQLite absorbs the duplicate `CREATE TABLE IF NOT EXISTS`, so it
        // is invisible there; on a real MySQL lane it fires `1050 Table already
        // exists`. Restored to the de-duplicated form. A bite proof that edits
        // THIS file must restore it in the same breath -- every lane reads it.
        return array_merge([$dial], $offDial);
    }

    /**
     * TRUE when $path names something the dial directory already yields —
     * either the directory itself or a file directly inside it.
     *
     * Directory-boundary safe on purpose: the needle carries the trailing
     * separator, so `.../Migrations-extra` is NOT reported as inside
     * `.../Migrations`. String comparison, not a regex: src/Teleframe is
     * zero-regex (AGENTS.md).
     */
    private static function isInside(string $path, string $directory): bool
    {
        if ($path === $directory) {
            return true;
        }

        $prefix = rtrim($directory, '/').'/';

        return strncmp($path, $prefix, strlen($prefix)) === 0;
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

        // An RPC result that constructs no Update: it owes no tf_updates row, so
        // it must NOT be handed to writeUpdate() — that would mint a row in the
        // UPDATE fact log for a thing that is not an update. See RPC_RESULTS.
        if (in_array($ctor, self::RPC_RESULT_CTORS, true)) {
            $this->clue("UpdateIngestor: '{$ctor}' is an RPC result carrying no update — no update row owed");

            return null;
        }

        // A carrier: an RPC result whose Vector<Update> members are the REAL
        // updates. Derived from the scheme (see updateCarriers()), so the
        // difference family's `other_updates` is walked exactly as `updates` and
        // `updatesCombined` have always been walked.
        $carriers = self::updateCarriers();
        if (isset($carriers[$ctor])) {
            return $this->writeUpdateContainer($payload, $accountId, $carriers[$ctor]);
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
     * The curated `$fill` keys off the TABLE, never off what the payload
     * happens to carry: the two messages surfaces are different arities
     * (tf_messages 22 columns, tf_messages_service 13, sharing 12) and a
     * `messageService` payload can still carry a `message` header. A key that
     * is written because the payload has it — rather than because the table
     * has the column — raises an unknown-column SQL error, and because
     * `ingest()` wraps this in a transaction, that error does not merely skip
     * the message: it rolls back the caller's enclosing work, including the
     * tf_updates fact row and its 17 children when this runs on a nested
     * message inside `writeUpdate()`. See the gate in
     * tests/Ingest/ServiceMessageHasNoTextColumnTest.php.
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeMessage(array $payload, int $accountId, string $ctor): TfMirrorModel
    {
        $table = self::MESSAGE_TABLES[$ctor] ?? 'tf_messages';
        $modelClass = $table === self::TF_MESSAGES_SERVICE ? TfMessageService::class : TfMessage::class;

        $id = (int) ($payload['id'] ?? 0);
        // `?? null` IS LOAD-BEARING, and this line is where it was found.
        // `message#7600b9d3` declares `peer_id:Peer` mandatory and unflagged, so
        // a real connection always supplies it — and a bare `$payload['peer_id']`
        // read therefore LOOKS free of consequence while raising
        // `ErrorException: Undefined array key "peer_id"` on any payload that
        // does not, inside the `DB::transaction` this whole path runs in, so the
        // raise rolls back the caller's enclosing update and all 17 of its
        // children with it. That is the same blast radius as the `message`
        // column defect above, on the same line of the same method. The delete
        // path below already reads its peer as `$payload['peer'] ?? null`;
        // this read now matches it.
        [$peerType, $peerId] = PeerShapeTool::normalize(self::peerValue($payload['peer_id'] ?? null));

        $fill = [
            'account_id' => $accountId,
            'id' => $id,
            'constructor' => $ctor,
            'peer_type' => $peerType,
            'peer_id' => $peerId,
            'date' => (int) ($payload['date'] ?? 0),
        ];
        foreach (self::MESSAGE_BOOLS[$table] as $bool) {
            $fill[$bool] = (bool) ($payload[$bool] ?? false);
        }

        // `message` is a tf_messages column and NOT a tf_messages_service
        // column: the shipped DDL gives the two surfaces 22 vs 13 columns,
        // sharing 12, because a service message's body IS its action. So the
        // key is added behind the SAME table test the search index below
        // already used — the payload field is present on a service message
        // (a nested `message` header can ride along) but the column is not,
        // and writing it unconditionally raised
        // `table tf_messages_service has no column named message` on EVERY
        // messageService, rolling back the whole enclosing transaction: the
        // tf_updates fact row and all 17 tf_updates_* children with it. Keyed
        // on the table, not on the payload, because the payload's presence of
        // `message` says nothing about the table's arity.
        if ($table !== self::TF_MESSAGES_SERVICE) {
            $fill['message'] = (string) ($payload['message'] ?? '');
        }

        // THE WRITE KEY IS THE TABLE'S REAL PRIMARY KEY, all four columns.
        //
        // It was `['account_id','id']`, which is what the shipped DDL declared
        // until 2026-10-03 — and which silently overwrote 80.98% of a real
        // account's history, because Telegram numbers messages PER CHAT:
        // `messages.getHistory` takes a MANDATORY `peer:InputPeer` and a
        // peer-relative `offset_id`, and `message#7600b9d3` carries `id:int` AND
        // `peer_id:Peer` in the SAME constructor. 136 of one account's dialogs
        // hold a message numbered 1. `updateOrInsert` never raises on a key
        // collision — the second message's body, date and flags simply REPLACE
        // the first's — so a multi-peer crawl destroyed history with no clue.
        //
        // The halves come from the row this method has ALREADY filled, so they
        // cannot disagree with what was written.
        DB::table($table)->updateOrInsert([
            'account_id' => $accountId,
            'peer_type' => $peerType,
            'peer_id' => $peerId,
            'id' => $id,
        ], $fill);

        // Populate the search index for the CONTENT side only, under the same
        // table test: tf_messages_service has no text to index, and a token
        // table keyed by a row with no body would be a lie. The peer pair is
        // passed for the same reason the write key above carries it —
        // `index()` REPLACES the tokens of the message it is handed, so a bare
        // id would erase every other dialog's tokens for that id.
        if ($table !== self::TF_MESSAGES_SERVICE) {
            $this->searchIndex()->index($accountId, $id, (string) ($payload['message'] ?? ''), $peerType, $peerId);
        }

        $this->writeChildrenFromCatalog($table, $accountId, $payload, $fill);

        // The re-read carries the peer too. `where('id',$id)` matched a row in
        // ANY dialog once ids are peer-scoped, so it could return a different
        // dialog's message and hand THAT back as the ingest result — the same
        // cross-dialog confusion one layer up from the write key.
        /** @var TfMirrorModel|null $stored */
        $stored = $modelClass::forAccount($accountId)
            ->where('peer_type', $peerType)
            ->where('peer_id', $peerId)
            ->where('id', $id)
            ->first();
        if ($stored === null) {
            throw new RuntimeException("UpdateIngestor: failed to re-read '{$table}' row ({$accountId}, {$peerType}, {$peerId}, {$id}) after write");
        }

        return $stored;
    }

    /**
     * Write every child row the CATALOG declares for this parent, using the
     * very same decomposer the live mirror seam uses.
     *
     * This is the whole point of the class-level decision: the child set is no
     * longer enumerated anywhere in this file. It was
     * `['from_id', 'saved_peer_id']` — two of the 29 children
     * `mirror-catalog.json` declares for tf_messages — which is why backfill
     * and the public API silently stored a third of what the bus stored for the
     * same message. Now a child is written here exactly when the catalog says
     * the parent has one AND the payload carries it (row existence = fact
     * existence), which is the same rule the live path applies, so the two
     * writers cannot disagree about WHICH children exist without the change
     * that makes them disagree being visible in one file.
     *
     * `$parentRow` is this class's own curated fill, not the decomposer's parent
     * row: the parent column dial (MESSAGE_BOOLS, peer halves, the search
     * index) is deliberately NOT duplicated here. The decomposer is asked for
     * children only, and its rows[0] — the parent it prepends — is dropped so
     * the parent is written exactly once, by exactly one writer.
     *
     * Placement goes through `MirrorFactWriter` rather than a bare
     * `updateOrInsert` so an unmodelled column is dropped-and-clued (the writer
     * asks the DDL which columns exist) instead of raising, and an FK/NOT NULL
     * failure becomes a clue string instead of killing the ingest. Those are
     * the semantics the live path already has; inheriting them is the point.
     *
     * The writer is intentionally NOT constructed as a property: it is
     * stateful (per-table column-listing and primary-key caches) and this
     * class's constructor is pinned to exactly ['events', 'logger'] by
     * InjectableClockTest. So it is built per call, which is what the mirror
     * seam does too.
     *
     * @param  array<string, mixed>  $payload  the wire message object
     * @param  array<string, mixed>  $parentRow  the row just written to $table
     */
    private function writeChildrenFromCatalog(
        string $table,
        int $accountId,
        array $payload,
        array $parentRow,
    ): void {
        $decomposed = $this->decomposer()->decomposeChildren($table, $accountId, $parentRow, $payload);
        foreach ($decomposed['clues'] as $clue) {
            $this->clue($clue);
        }

        $rows = [];
        foreach ($decomposed['rows'] as $row) {
            if ($row['table'] === $table) {
                continue; // the parent — written above, by this class
            }

            $rows[] = $row;
        }

        if ($rows === []) {
            return;
        }

        $written = (new MirrorFactWriter)->write(DB::connection(), $rows);
        foreach ($written['fkClues'] as $clue) {
            $this->clue($clue);
        }

        foreach ($written['unmappedClues'] as $clue) {
            $this->clue($clue);
        }

        // THE FOURTH BUCKET, and the one this method dropped: the two loops
        // above ended the forwarding chain, so a value fault on a CHILD row —
        // "the value does not fit the column the DDL gives it" (MySQL
        // 1265/1364/1366/1406, Postgres 22001) — was computed by the writer,
        // carried back across the public `Teleframe::ingest()` seam, and then
        // thrown away with nobody left to report it. On the public path that is
        // the same anonymity as no ledger at all: the row is simply absent from
        // the mirror and no log line says why. Its own loop, its own
        // `clue()` call, and nothing folded into the FK list — a value fault is
        // a column-shape fault, and filing it under the relationship axis sends
        // the reader to the wrong graph.
        foreach ($written['valueClues'] as $clue) {
            $this->clue($clue);
        }
    }

    /**
     * The catalog-derived child decomposer, built once per process.
     *
     * Built from the COMMITTED artifacts, the same construction
     * `MirrorIngesterFactory::build()` and the chunk decomposer use, so this
     * path cannot serve a stale or hand-maintained child list. Memoised
     * because `TlParser::parseFile()` re-reads and re-parses the whole scheme
     * — doing that per message would make backfill quadratic in the file.
     *
     * Resolved through `Schema\SchemaArtifacts` rather than the Laravel
     * `SchemaArtifacts` facade wrapper: this class is plain-PHP constructible
     * (bin/standalone-smoke.php constructs it with no container), and the
     * package-relative locator is the one that resolves in a vendor install.
     * `self::$decomposer` is set only on success, so a failed build is retried
     * rather than cached as a broken instance.
     */
    private function decomposer(): MirrorFactDecomposer
    {
        if (self::$decomposer instanceof MirrorFactDecomposer) {
            return self::$decomposer;
        }

        $scheme = TlParser::parseFile(self::schemePath());
        $catalog = MirrorCatalog::load(SchemaArtifacts::path('mirror-catalog.json'), $scheme);

        return self::$decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog);
    }

    /**
     * The layer-227 scheme file this package ships.
     *
     * `schema/sources/TL_telegram_v227.tl` sits at the PACKAGE ROOT, not under
     * `src/Schema/schema/sources/` (measured: `src/Schema/schema/sources/`
     * holds api/mtproto/botapi sources and no TL_telegram_v227), which is why
     * this resolves it from the package root the same way
     * `Laravel\SchemaArtifacts::TL_RELATIVE` does — as a constant, not as a
     * second hand-maintained path list.
     */
    private static function schemePath(): string
    {
        return dirname(__DIR__, 3).'/schema/sources/TL_telegram_v227.tl';
    }

    /**
     * The search indexer.
     *
     * Constructed inline rather than injected: InjectableClockTest pins this
     * class's constructor to exactly ['events', 'logger'], and a third seam is
     * not worth reopening a deliberate decision for. The indexer is stateless,
     * so there is nothing to inject.
     */
    private function searchIndex(): MessageSearchIndexer
    {
        return new MessageSearchIndexer;
    }

    /**
     * The delete seam, built with the same dispatcher the ingestor was given so
     * a `MessagesDeleted` event reaches the same listeners as `UpdateStored`.
     *
     * Provenance: src/Teleframe/Ingest/SafeDelete.php (the purge of both message
     * parents, the 38 `tf_messages*` children and the search tokens).
     */
    private function safeDelete(): SafeDelete
    {
        return new SafeDelete($this->events);
    }

    /**
     * Decompose a single update ctor into tf_updates (keyed (account_id,
     * seq, position)) plus its hand-authored children, and route a nested
     * message object to the messages mirror.
     *
     * seq / position default to 0 / 0 for a lone update (the seq-less short
     * dials and any direct update* ingest); a container caller threads the
     * container's delivering seq and the member's slot position so every
     * member of a multi-update container lands on a distinct key. The child
     * set written for a key is a full snapshot of THIS ctor: rows a prior
     * occupant of the key left behind are purged first (row existence = fact
     * existence — a stale child must never survive into a different ctor).
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeUpdate(array $payload, int $accountId, string $ctor, int $seq = 0, int $position = 0): Model
    {
        $key = ['account_id' => $accountId, 'seq' => $seq, 'position' => $position];

        $row = [...$key, 'constructor' => $ctor];
        foreach (self::UPDATE_FOLDED_SCALARS as $field) {
            $value = $payload[$field] ?? null;
            if ($value !== null) {
                $row[$field] = (int) $value;
            }
        }
        DB::table('tf_updates')->updateOrInsert($key, $row);

        // The FK parent now exists for the key, so the purge is safe: drop
        // the prior update's children, then write this ctor's facts fresh.
        $this->deleteUpdateChildren($key);
        $this->writeUpdateChildren($payload, $key);

        $nested = $payload['message'] ?? null;
        if (is_array($nested) && in_array((string) ($nested['_'] ?? ''), self::MESSAGE_CTORS, true)) {
            $this->writeMessage($nested, $accountId, (string) $nested['_']);
        }

        $stored = TfUpdate::forAccount($accountId)->where('seq', $seq)->where('position', $position)->first();
        if ($stored === null) {
            throw new RuntimeException("UpdateIngestor: failed to re-read 'tf_updates' row ({$accountId}, {$seq}, {$position}) after write");
        }

        // A delete update's side effect runs LAST, after its own fact row and
        // every child of that row are already written.
        //
        // Why here and not earlier: the update IS a fact. `updateDeleteMessages`
        // says "these messages ceased to exist at pts N" — that is a true thing
        // that happened and it must survive the operation it describes. Purging
        // before the fact row is written would mean a crash mid-delete leaves
        // messages gone with no record of why, which is a strictly worse mirror
        // than the one this goal is fixing. Running inside the same transaction
        // as the fact write (ingest/ingestBatch both wrap it) means the delete
        // and its record commit together or not at all.
        //
        // Why the two cannot fight: the purge is scoped to `tf_messages*` and
        // `tf_message_search`, keyed by the message's peer-scoped key
        // (account_id, peer_type, peer_id, id) — widened 2026-10-03, because
        // Telegram numbers message ids per chat and (account_id, id) names a
        // different dialog's message. This update's rows are `tf_updates*`,
        // keyed (account_id, seq, position). The key spaces do
        // not overlap, so no row this update just wrote — including the
        // `tf_updates_messages` child rows that RECORD the deleted ids, which
        // are the audit trail of the delete — can be purged by its own side
        // effect. A nested `message` on the same ctor is also safe: the
        // nested write above lands first and the purge only touches ids the
        // payload names in `messages`, which for a delete ctor is the
        // deletion set by definition.
        $this->applyDeleteSideEffect($payload, $accountId, $ctor);

        return $stored;
    }

    /**
     * Apply a delete update's side effect on the messages mirror.
     *
     * `updateDeleteMessages` and `updateDeleteChannelMessages` are the same
     * fact with a different scope word: the first deletes across the account's
     * non-channel peers, the second within one channel. Since the dial widened
     * BOTH surfaces to (account_id, peer_type, peer_id, id), the narrower
     * predicate EXISTS and is what runs: SafeDelete::deleteMessages() purges
     * only the named dialog's ids, off `peer:Peer` or `channel_id` (read also
     * to give MessagesDeleted a peer). An absent or empty peer half
     * normalizes to null, and then it stays ACCOUNT-WIDE by choice: broad.
     *
     * A never-mirrored id is handled by SafeDelete itself: zero rows removed,
     * no raise. That is deliberate — this runs inside the ingest transaction,
     * so raising on an id the mirror never stored would roll back the update's
     * own fact row and make the delete un-ingestable forever.
     *
     * @param  array<string, mixed>  $payload
     */
    private function applyDeleteSideEffect(array $payload, int $accountId, string $ctor): void
    {
        if ($ctor !== 'updateDeleteMessages' && $ctor !== 'updateDeleteChannelMessages') {
            return;
        }

        $ids = $payload['messages'] ?? null;
        if (! is_array($ids) || ! array_is_list($ids) || $ids === []) {
            $this->clue("UpdateIngestor: {$ctor} carries no list \"messages\" member — no message rows purged");

            return;
        }

        $messageIds = [];
        foreach ($ids as $id) {
            $messageIds[] = (int) $id;
        }

        // The peer the deleted ids belong to, and it is NOT optional in
        // practice: `updateDeleteMessages` carries `peer:Peer` and
        // `updateDeleteChannelMessages` carries `channel_id`. Telegram numbers
        // messages per chat, so an id-scoped delete would purge message N from
        // EVERY dialog of the account. `SafeDelete::deleteMessages()` documents
        // why an unknown peer stays account-wide rather than narrowing.
        //
        // The two legs are nulled TOGETHER or not at all. `tf_messages`' primary
        // key is (account_id, peer_type, peer_id, id), so a half-set peer —
        // `peer_type` present with a null `peer_id`, or the reverse — cannot
        // address the indexed path at all: it falls off the peer-scoped index
        // into a scan, and the peer-scoped delete downstream can then match the
        // wrong dialog. A row is either "scoped to this peer" or "no peer", and
        // there is no third, half-resolved shape. Nulling each leg
        // independently left 3 of the 4 type/id combinations reachable.
        if ($ctor === 'updateDeleteChannelMessages') {
            $resolvedType = PeerShapeTool::PEER_CHANNEL;
            $resolvedId = isset($payload['channel_id']) ? (int) $payload['channel_id'] : 0;
        } else {
            $normalized = PeerShapeTool::normalize(self::peerValue($payload['peer'] ?? null));
            $resolvedType = $normalized[0];
            $resolvedId = $normalized[1];
        }

        // One verdict, applied to BOTH legs. `normalize()` signals "no peer" as
        // a zero in either position, and a channel update with no `channel_id`
        // is the same signal, so half a peer is not a thing this row can be.
        $hasPeer = $resolvedType !== 0 && $resolvedId !== 0;

        $peerType = $hasPeer ? $resolvedType : null;
        $peerId = $hasPeer ? $resolvedId : null;

        $this->safeDelete()->deleteMessages($accountId, $messageIds, $peerId, $peerType);
    }

    /**
     * The update-CARRIER map, derived from the parsed layer-227 scheme.
     *
     * ctor → the name of its `Vector<Update>` field. Every constructor whose
     * declared result type is `Update` is an update endpoint and is NOT here;
     * every constructor that returns `Updates` / `updates.Difference` /
     * `updates.ChannelDifference` AND declares a `Vector<Update>` is a carrier.
     *
     * `updatesTooLong#e317af7e = Updates;` declares no field, so it is absent
     * and reaches the `RPC_RESULTS` routing instead, which owes it no row. That
     * is why it needs no special case here.
     *
     * Memoised per process for the same reason `decomposer()` is: it parses the
     * whole scheme, and a per-message rebuild would make ingest quadratic in the
     * size of that file. The static holds only the derived map, so a failed
     * build is retried rather than cached as a broken instance.
     *
     * @return array<string,string>
     */
    private static function updateCarriers(): array
    {
        if (self::$updateCarriers !== null) {
            return self::$updateCarriers;
        }

        $carriers = [];
        foreach (TlParser::parseFile(self::schemePath())->types() as $type) {
            foreach ($type->constructors() as $name => $constructor) {
                if ($constructor->resultType === 'Update') {
                    continue; // an update endpoint, not a carrier of updates
                }

                foreach ($constructor->allParams() as $param) {
                    if ($param->baseType() !== 'Update') {
                        continue;
                    }

                    $kind = $param->kind();
                    if ($kind === 'vector' || $kind === 'ref') {
                        $carriers[(string) $name] = $param->name;

                        break;
                    }
                }
            }
        }

        return self::$updateCarriers = $carriers;
    }

    /**
     * The members a carrier field holds, as a list.
     *
     * A carrier's field is either a LIST of updates — `updates`,
     * `updatesCombined`, `updates.difference`, `updates.differenceSlice`,
     * `updates.channelDifference` all declare `Vector<Update>` — or a SINGULAR
     * `Update` ref, which is `updateShort#78d4dec1 update:Update date:int =
     * Updates;` and holds exactly one. Normalising both shapes here is what lets
     * `updateShort` be a carrier on the same code path as `updates` rather than
     * needing its own writer.
     *
     * Anything else — absent, a scalar, a list of non-arrays — is not a member
     * list and yields nothing, which is the same answer an empty vector gives.
     *
     * @return list<mixed>
     */
    private static function carrierMembers(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        if (array_is_list($value)) {
            return $value;
        }

        return isset($value['_']) ? [$value] : [];
    }

    /**
     * Decompose an update-CARRIER envelope into each nested single update.
     *
     * `$field` is the carrier's `Vector<Update>` field name, DERIVED from the
     * scheme rather than assumed: `updates` and `updatesCombined` carry
     * `updates`, and the whole difference family carries `other_updates`.
     * Reading a hardcoded `'updates'` here is what silently dropped the
     * `other_updates` of `updates.difference`, `updates.differenceSlice` and
     * `updates.channelDifference` — the replies to the very calls that fetch
     * missed updates. Measured: before the fix, ingesting the
     * `updates.difference` fixture wrote one row named `updates.difference` and
     * never touched the `updateChannel` it carried.
     *
     * The container's delivering seq (payload.seq — `updates` /
     * `updatesCombined`; the difference family carries none at top level, and
     * its `state.seq` is the window marker rather than a delivery seq, so it
     * reads as 0) becomes the seq of every member row, and each member's slot
     * index in the vector becomes its position — a multi-update carrier can no
     * longer collapse every member onto (0, 0).
     *
     * @param  array<string, mixed>  $payload
     */
    private function writeUpdateContainer(array $payload, int $accountId, string $field): ?Model
    {
        $members = self::carrierMembers($payload[$field] ?? null);
        $first = null;
        $seq = (int) ($payload['seq'] ?? 0);
        if ($members !== []) {
            $position = 0;
            foreach ($members as $item) {
                if (is_array($item)) {
                    $ctor = (string) ($item['_'] ?? '');
                    if (str_starts_with($ctor, 'update') && ! isset(self::updateCarriers()[$ctor])
                        && ! in_array($ctor, self::RPC_RESULT_CTORS, true)) {
                        // Every member is written; only the FIRST becomes the
                        // returned root. `$first ??= $this->writeUpdate(...)`
                        // short-circuited the CALL as well as the assignment, so
                        // a container silently stored only its first member and
                        // dropped the rest — a `updateDeleteMessages` in second
                        // position was never seen, let alone acted on.
                        $root = $this->writeUpdate($item, $accountId, $ctor, $seq, $position);
                        $first ??= $root;
                    }
                }
                $position++;
            }
        }

        if ($first === null) {
            $this->clue("UpdateIngestor: carrier carries no single-update members in \"{$field}\" — nothing stored");
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
                // `??=` SHORT-CIRCUITS THE CALL, not just the assignment. When
                // this read `$first ??= $this->writeMessage(...)`, the page's
                // SECOND AND EVERY LATER message were never handed to the
                // writer at all — the left operand was already non-null, so
                // the right operand was never evaluated.
                //
                // MEASURED live against the operator's account: a crawl
                // reporting `{messages:21, requests:6}` left
                // `SELECT COUNT(*) FROM tf_messages` at 6 — exactly one row
                // per REQUEST, not per message. 15 of 21 messages were never
                // written, and nothing anywhere reported an error: there was
                // no failure to report, only a call that never happened.
                //
                // The call is made unconditionally and `$first` only remembers
                // the first RESULT. Every member is written; only the first
                // becomes the returned root — which is what the sibling branch
                // in `writeUpdate()` already does.
                $written = $this->writeMessage($item, $accountId, $ctor);
                $first ??= $written;
            }
        }

        if ($first === null) {
            $this->clue('UpdateIngestor: envelope carries no message ctor members — nothing stored');
        }

        return $first;
    }

    /**
     * Purge every child row a PRIOR update left at this fact key. Children
     * are 1:1 / 1:N presence facts keyed (account_id, seq, position) — row
     * existence is fact existence, so a different ctor landing on the same
     * key must not inherit the previous ctor's children (fact contamination).
     *
     * @param  array{account_id: int, seq: int, position: int}  $key
     */
    private function deleteUpdateChildren(array $key): void
    {
        foreach (self::UPDATE_CHILD_TABLES as $table) {
            DB::table($table)
                ->where('account_id', $key['account_id'])
                ->where('seq', $key['seq'])
                ->where('position', $key['position'])
                ->delete();
        }
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
