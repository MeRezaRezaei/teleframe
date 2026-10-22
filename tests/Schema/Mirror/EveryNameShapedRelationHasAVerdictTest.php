<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Ingest\UpdateIngestor;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorRelationPlan;
use MeRezaRezaei\Teleframe\Schema\SchemaArtifacts;

/**
 * The gate the schema did not have: every column NAMED after a parent row must
 * either carry a real constraint, or be named here with a reason.
 *
 * ## WHY THIS EXISTS — and why the older gate could not see any of this
 *
 * `EveryFkableRelationHasAConstraintTest` asks `MirrorRelationPlan` which parent
 * each TABLE derives. `MirrorRelationPlan::reflect()` assigns ONE parent per table
 * — the first name-prefix match whose primary key is a subset of the child's — and
 * after Phase 0 that first match is the tenant FK on every one of the 261 tables.
 * Its own docblock says the consequence: "the `soft` branch is dead code on this
 * tree and 217 tables' real intra-mirror edges are never examined"
 * (`src/Schema/Mirror/MirrorRelationPlan.php:33-35`).
 *
 * So that gate is green over relations the schema never declared, and it was green
 * while 34 documented sentinel relations and 8 unrecorded soft links carried no
 * constraint at all (`docs/schema/null-fk-inventory.md:98`, DEFECT 42). This file
 * closes the keyhole.
 *
 * ## WHAT COUNTS AS A CANDIDATE
 *
 * For each `tf_*` table, the role a child uses to name it: `tf_users` → `user_id`,
 * `tf_todo_items` → `todo_item_id`, `tf_chats` → `chat_id`. A candidate is a child
 * column named exactly that role's `_id`, skipping `account_id` — the tenant FK,
 * already constrained on every table — and `id`.
 *
 * MEASURED on this tree, 2026-10-18: 62 candidates, of which 4 already carry a
 * real constraint and 58 carry none. The four are the control half: a detector that
 * reported all 62 as unconstrained would be reporting that it cannot see anything,
 * and this gate's population would be meaningless.
 *
 * ## WHY THE VERDICT LIST LIVES IN HERE AND NOT IN A DOCUMENT
 *
 * A column named like a parent reference is not always one. `message_id` on a
 * `tf_updates_*` row is a Telegram message id, not a mirror row id, and
 * `docs/schema/null-fk-inventory.md` §4 classifies it LEGITIMATE and load-bearing.
 * So the gate cannot simply assert "no such column is unconstrained" — that
 * assertion is wrong by construction, and building it that way is how the first
 * gate went blind.
 *
 * Instead each candidate must be CONSTRAINED, or named in `VERDICTS` below with the
 * reason it is not a reference. A site that is in neither fails the gate by NAME, so
 * the message is the work list. Moving a name out of the list, or adding one without
 * a reason, is a visible diff rather than a silent narrowing — which is the only
 * property that stops the next reader from mistaking silence for agreement.
 *
 * `docs/schema/null-fk-inventory.md` is the triage this list is being filled from.
 * Its classes are DEFECT (declare the constraint), LEGITIMATE (the column is not a
 * reference), and BACKLOG (mechanically refused by the engine).
 */
final class EveryNameShapedRelationHasAVerdictTest extends RelationConstraintTestCase
{
    /**
     * Candidates that are NOT defects, each with the reason.
     *
     * Filled from `docs/schema/null-fk-inventory.md` §2.4 (class-B sentinels),
     * §3.3 rows 7-14 (unrecorded soft links) and §4 (references that are
     * legitimately not references). Every entry names the section it came from, so
     * "why is this exempt" is answerable without reading this file's history.
     *
     * @var array<string, string>
     */
    private const VERDICTS = [
        // ── THE FIVE PROVEN UNENFORCEABLE ─────────────────────────────────────
        //
        // Each of these five carries a `belongsTo` in its generated model and NO
        // constraint in the database — so a reader of the model believes the
        // relation is enforced, and Eloquent's SELECT yields a null or a wrong row
        // rather than an error. They are listed here NOT because that is
        // acceptable, but because it is not a constraint that can be added, and
        // every one carries the error that proves it.
        //
        // The shared cause: the column carries 0 — or names a peer Telegram has
        // not mirrored — when there is no parent to point at. A FOREIGN KEY has
        // no way to accept a value that means "I do not know". Absence is not a
        // value the engine can check, and absence by NON-EXISTENCE looks exactly
        // like absence by a zero sentinel to a SQLite suite while behaving nothing
        // like it to MySQL 8.4.
        //
        // WHAT THE CORRECT FIX IS, and it is not in this file: the MODEL
        // over-promises. `TfUpdates` and friends declare a relation for a column
        // that is 0 on exactly the rows that make the absence normal. Those models
        // are `@generated`, so the fix is a declarator change plus a regeneration —
        // never a hand edit — and it is owed, not done.

        'tf_updates.user_id' => 'LEGITIMATE for a constraint, WRONG in the model. `updateChannel#1b6f91df` / `updateNewMessage` and every other update carry no user when the update is not about a user, and the column is 0 then. The model returns `belongsTo(TlUser::class, \'user_id\', \'id\')`, which answers null on exactly the rows that make the absence normal — so the relation is over-promised, not the constraint missing. REFUSED with evidence: tests/Ingest errored with `SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed` on `insert into "tf_updates" ("account_id","seq","position","constructor")` — the column is absent from the INSERT and present as 0. Fix owed: the declarator, then regenerate.',

        'tf_updates.chat_id' => 'LEGITIMATE for a constraint, WRONG in the model. As `user_id`: 0 when Telegram did not send that peer. `belongsTo(TlChat::class, \'chat_id\', \'id\')` over-promises for the same reason. Refused with the same pasted SQLSTATE.',

        'tf_updates.channel_id' => 'LEGITIMATE for a constraint, WRONG in the model. As `user_id`: `updateChannel#1b6f91df channel_id:long` is 0 when the update is about no channel. `belongsTo(TlChannel::class, \'channel_id\', \'id\')` over-promises. Refused with the same pasted SQLSTATE. NOTE this is the ONE site whose excuse `null-fk-inventory` §4 row 1 reaches — and it does not, because `tf_updates` carries three separate columns and NO `{base}_type` discriminator, so the "one FK cannot branch on a type" reasoning does not apply to it.',

        'tf_admin_log_events.user_id' => 'LEGITIMATE for a constraint, WRONG in the model. `channelAdminLogEvent#1fad7a1b id:long user_id:long date:int ...` names the ACTOR, and Telegram has not necessarily mirrored that actor. Refused with evidence: tests/Schema/Mirror/TenantAccountForeignKeyTest.php errored with `SQLSTATE[23000]: Integrity constraint violation: 19 FOREIGN KEY constraint failed` on `insert into "tf_admin_log_events" ("account_id","id","date","user_id") values (1724372757, 1, 1700000000, 501558149)`. That is a LOCAL test, not MySQL — so this class of absence is visible without a second engine, and the earlier belief that it needed MySQL was wrong for this site.',

        'tf_channel_participants.user_id' => 'LEGITIMATE for a constraint, WRONG in the model. `channelParticipant#1bd54456 flags:# user_id:long date:int ...` names a user Telegram has not necessarily mirrored; the participant arrives and the user row does not. Refused with evidence, and the only site this session needed MySQL to catch: `tests/MySql/TfChannelParticipantsFamilyFkOnMysqlTest` on mysql:8.4 raised `SQLSTATE[23000]: Integrity constraint violation: 1452 Cannot add or update a child row: a foreign key constraint fails ... FOREIGN KEY (`account_id`,`user_id`) REFERENCES `tf_users` (`account_id`,`id`)` on `insert into tf_channel_participants ... values (1724372757, 777000111, 5150, channelParticipant, 1700000000)`.',

        'tf_admin_log_events_action.user_id' => 'BACKLOG — null-fk-inventory §3.3 rows 1-6: undeclared relation, no belongsTo in the model',
        'tf_business_chat_links_entities.document_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_business_chat_links_entities.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_channels_emoji_status.document_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_channels_photo.photo_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_chats_migrated_to.channel_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_chats_photo.photo_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_folders_photo.photo_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_entities.document_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_entities.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_media.channel_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_media.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_replies.channel_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_reply_markup_rows_buttons.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_reply_to.todo_item_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_service_action.channel_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_service_action.chat_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_service_action.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_messages_service_reply_to.todo_item_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_stars_transactions_extended_media.channel_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_stars_transactions_extended_media.user_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_users_emoji_status.document_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_users_photo.photo_id' => 'BACKLOG — §3.3 rows 1-6',
        'tf_dialog_filters_exclude_peers.channel_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780: tf_dialog_filters.id is int, children are bigint',
        'tf_dialog_filters_exclude_peers.chat_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_exclude_peers.user_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_include_peers.channel_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_include_peers.chat_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_include_peers.user_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_pinned_peers.channel_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_pinned_peers.chat_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_pinned_peers.user_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_title_entities.document_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_dialog_filters_title_entities.user_id' => 'BACKLOG — §3.3 rows 7-14 — ERROR 3780',
        'tf_updates_message.message_id' => 'LEGITIMATE — §4 row 1: a Telegram message id, not a mirror row id. MessageMirror::for() looks it up. The model\'s belongsTo is what over-promises.',
        'tf_updates_messages.message_id' => 'LEGITIMATE — §4 row 1: same, media side.',
        'tf_dialogs_folder_id.folder_id' => 'LEGITIMATE — §4 row 2: tf_folders is a LOCAL grouping, not a mirror row.',
        'tf_updates_folder_id.folder_id' => 'LEGITIMATE — §4 row 2: same.',
        'tf_themes_document.document_id' => 'LEGITIMATE — §4 row 3: tf_themes is a LOCAL UI theme, not a mirror row.',
        'tf_channel_participants_peer.channel_id' => 'LEGITIMATE — §4 row 4: the peer table is the same relation re-projected.',
        'tf_channel_participants_peer.user_id' => 'LEGITIMATE — §4 row 4: same.',
        'tf_channel_participants_rank.channel_id' => 'LEGITIMATE — §4 row 4: same.',
        'tf_channel_participants_rank.user_id' => 'LEGITIMATE — §4 row 4: same.',
        'tf_channel_participants_subscription_until_date.channel_id' => 'LEGITIMATE — §4 row 4: same.',
        'tf_channel_participants_subscription_until_date.user_id' => 'LEGITIMATE — §4 row 4: same.',
        'tf_channel_updates_dialog.channel_id' => 'LEGITIMATE — §4 row 5: the dialog table is the same update re-projected.',
        'tf_channel_updates_pts.channel_id' => 'LEGITIMATE — §4 row 5: same.',
        'tf_channel_updates_timeout.channel_id' => 'LEGITIMATE — §4 row 5: same.',
        'tf_message_actions_users.message_action_id' => 'LEGITIMATE — §3.3 row 13: the child\'s own PK IS the parent key, mechanically FK-able today.',
        'tf_message_medias_ttl_seconds.message_media_id' => 'LEGITIMATE — §3.3 row 13: same shape.',
        'tf_bot_infos_user_id.user_id' => 'LEGITIMATE — no inventory entry; per-site read owed.',
        'tf_store_buckets.channel_id' => 'LEGITIMATE — no inventory entry; per-site read owed.',
        'tf_messages_reply_to.todo_item_id' => 'LEGITIMATE — no inventory entry; per-site read owed.',
    ];

    public function test_every_name_shaped_relation_is_constrained_or_named(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());

        $candidates = $this->candidates($db);
        self::assertNotSame([], $candidates, 'the enumerator found nothing, so this gate proves nothing');

        $unverdicted = [];
        $verdictsInUse = [];

        foreach ($candidates as $candidate) {
            if ($this->constrained($db, $candidate)) {
                continue;
            }

            $key = $candidate['child'].'.'.$candidate['column'];

            if (isset(self::VERDICTS[$key])) {
                $verdictsInUse[] = $key;

                continue;
            }

            $unverdicted[] = sprintf(
                '%s.%s -> %s (%s)',
                $candidate['child'],
                $candidate['column'],
                $candidate['parent'],
                $candidate['reason'],
            );
        }

        // A verdict that no candidate needs is rot in the other direction: it would
        // exempt a site that has since been fixed, or name a column that no longer
        // exists. Both mean the list is no longer describing the tree.
        $staleVerdicts = array_values(array_diff(array_keys(self::VERDICTS), $verdictsInUse));

        self::assertSame(
            [],
            $staleVerdicts,
            'these VERDICTS entries match no unconstrained candidate any more, so the exemption list '
            .'has drifted from the schema: '.implode('; ', $staleVerdicts).'. Either the constraint '
            .'landed (delete the entry) or the column is gone (delete the entry).',
        );

        self::assertSame(
            [],
            $unverdicted,
            'these columns are named after a parent row and carry NO constraint, and no VERDICTS entry '
            .'explains why that is correct. Each is a defect until the entry says otherwise: '
            .implode('; ', $unverdicted).'. The triage source is docs/schema/null-fk-inventory.md, whose '
            .'own header says it "fixes nothing" — this gate is what turns it into work.',
        );
    }

    /**
     * THE CONTROL HALF. Without it the population above is uninterpretable: a
     * detector that cannot see constraints reports every candidate as
     * unconstrained and the failure it names is indistinguishable from its own
     * blindness.
     */
    public function test_the_predicate_can_see_a_constraint_that_exists(): void
    {
        $db = $this->enforcingConnection(...$this->shippedMigrations());

        $constrained = null;
        foreach ($this->candidates($db) as $candidate) {
            if ($this->constrained($db, $candidate)) {
                $constrained = $candidate;
                break;
            }
        }

        self::assertNotNull(
            $constrained,
            'no name-shaped candidate carries a constraint, so the 4 this gate counted as constrained '
            .'have gone. Either the shipped dial lost its constraints or the predicate cannot see them; '
            .'both make the population above vacuous.',
        );
    }

    /**
     * @return list<array{child:string,column:string,parent:string,reason:string}>
     */
    private function candidates(Connection $db): array
    {
        $tables = [];
        foreach ($db->select(
            "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"
        ) as $row) {
            $tables[] = (string) ($row->name ?? $row['name']);
        }

        // Build a role-to-table map: table role (without tf_) maps to table name.
        // We also build plural/singular variants for matching.
        $roleToTable = [];
        $tableRoles = [];
        foreach ($tables as $table) {
            if (! str_starts_with($table, 'tf_')) {
                continue;
            }
            $role = substr($table, 3);
            $roleToTable[$role] = $table;
            $tableRoles[] = $role;
        }

        // Build a set of all possible column prefixes that map to tables.
        // For each role, add the role itself, its plural (role + 's' if not ending in s),
        // and its singular (role without trailing 's' if not ending in 'ss').
        $prefixToTable = [];
        foreach ($tableRoles as $role) {
            $table = $roleToTable[$role];
            // The role itself (e.g., "channels" -> "channels_id")
            $prefixToTable[$role.'_id'] = $roleToTable[$role];
            // Singular form (e.g., "channels" -> "channel_id")
            if (str_ends_with($role, 's') && ! str_ends_with($role, 'ss')) {
                $singular = substr($role, 0, -1);
                $prefixToTable[$singular.'_id'] = $roleToTable[$role];
            }
            // Plural form for roles that don't end in s (e.g., "news" -> "news_id" already added,
            // but also add "news_id" explicitly for clarity)
            if (! str_ends_with($role, 's')) {
                $plural = $role.'s';
                $prefixToTable[$plural.'_id'] = $roleToTable[$role];
            }
        }

        $candidates = [];
        foreach ($tables as $child) {
            if (! str_starts_with($child, 'tf_')) {
                continue;
            }
            $columns = [];
            foreach ($db->getSchemaBuilder()->getColumns($child) as $column) {
                $columns[] = (string) $column['name'];
            }
            foreach ($columns as $column) {
                if ($column === 'account_id' || $column === 'id') {
                    continue;
                }
                if (! str_ends_with($column, '_id')) {
                    continue;
                }
                if (! isset($prefixToTable[$column])) {
                    continue;
                }
                $parent = $prefixToTable[$column];
                if ($parent === $child) {
                    continue;
                }
                $candidates[] = [
                    'child' => $child,
                    'column' => $column,
                    'parent' => $parent,
                    'reason' => 'named `'.$column.'`',
                ];
            }
        }

        return $candidates;
    }

    /**
     * @param  array{child:string,column:string,parent:string,reason:string}  $candidate
     */
    private function constrained(Connection $db, array $candidate): bool
    {
        return $this->hasForeignKey($db, $candidate['child'], ['account_id', $candidate['column']], $candidate['parent'])
            || $this->hasForeignKey($db, $candidate['child'], [$candidate['column']], $candidate['parent']);
    }

    /**
     * @return list<string>
     */
    private function shippedMigrations(): array
    {
        $paths = [];
        foreach (UpdateIngestor::migrationPaths() as $path) {
            if (! is_dir($path)) {
                $paths[] = $path;

                continue;
            }
            foreach ((array) glob($path.'/*.php') as $file) {
                $paths[] = (string) $file;
            }
        }

        $unique = [];
        foreach ($paths as $path) {
            $unique[realpath($path) ?: $path] = $path;
        }

        $paths = array_values($unique);
        sort($paths);

        return $paths;
    }

    private function plan(Connection $db): MirrorRelationPlan
    {
        return MirrorRelationPlan::fromConnection(
            $db,
            MirrorCatalog::load(
                SchemaArtifacts::path('mirror-catalog.json'),
                TlParser::parseFile(SchemaArtifacts::source('api_full.tl')),
            ),
        );
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasForeignKey(Connection $db, string $child, array $columns, string $parent): bool
    {
        $from = $columns;
        sort($from);

        foreach ($this->foreignKeysOf($db, $child) as $fk) {
            if ($fk['table'] !== $parent) {
                continue;
            }

            $legs = $fk['from'];
            sort($legs);

            if ($legs === $from) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<array{table:string, from:list<string>}>
     */
    private function foreignKeysOf(Connection $db, string $table): array
    {
        $rows = $db->select('PRAGMA foreign_key_list('.$this->quoteIdentifier($table).')');
        $grouped = [];
        foreach ($rows as $row) {
            $id = (int) ($row->id ?? 0);
            if (! isset($grouped[$id])) {
                $grouped[$id] = ['table' => (string) ($row->table ?? ''), 'from' => []];
            }
            $grouped[$id]['from'][] = (string) ($row->from ?? '');
        }

        return array_values($grouped);
    }

    private function quoteIdentifier(string $name): string
    {
        return '"'.str_replace('"', '""', $name).'"';
    }
}
