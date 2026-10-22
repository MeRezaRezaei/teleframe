<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Task 46.85, first six: the relations a GENERATED MODEL ALREADY ASSERTS and the
 * database does not enforce.
 *
 * ## WHY THESE SIX COME FIRST, WHEN THERE ARE FIFTY-EIGHT
 *
 * `tests/Schema/Mirror/EveryNameShapedRelationHasAVerdictTest.php` enumerates 58
 * child columns that are named after a parent row and carry no constraint. Fifty-two
 * of them are merely undeclared: the model says nothing, so a reader is misled by
 * nothing in particular.
 *
 * These six are different. The generated model carries a `belongsTo` for each one:
 *
 *   TfAdminLogEvents      belongsTo(TlUser::class,   'user_id',   'id')
 *   TfChannelParticipants belongsTo(TlUser::class,   'user_id',   'id')
 *   TfChannelUpdates      belongsTo(TlChannel::class,'channel_id', 'id')
 *   TfUpdates             belongsTo(TlUser::class,   'user_id',   'id')
 *   TfUpdates             belongsTo(TlChat::class,   'chat_id',   'id')
 *   TfUpdates             belongsTo(TlChannel::class,'channel_id', 'id')
 *
 * So a reader of those model files believes the constraint is there. It is not.
 * Eloquent's `belongsTo` issues a SELECT, so the failure is a null or a wrong row
 * rather than an error — which is the sentence the OTHER FK gate's own docblock
 * already uses: "a soft link is invisible until a query fails, and the failure is a
 * wrong answer rather than an error". This file is that failure, live.
 *
 * ## WHY `tf_updates`' PEER TRIAD IS NOT EXCUSED HERE
 *
 * `docs/schema/null-fk-inventory.md` §4 row 1 excuses a peer triad on tables that
 * carry a `{base}_type` discriminator, where one FOREIGN KEY genuinely cannot branch
 * on a type. `tf_updates` does not use a discriminator — it carries `user_id`,
 * `chat_id` and `channel_id` as three SEPARATE columns, each naming its own parent.
 * The excuse does not reach them and each is FK-able today.
 *
 * ## WHY THE LIST IS WRITTEN OUT AND NOT DISCOVERED
 *
 * Every earlier FK migration in this directory discovers its children from
 * `MirrorRelationPlan::singleParent()` and says why: "a hand-written join-column
 * list is a SECOND COPY of the schema and it drifts."
 *
 * That rule does not apply here, and the reason is worth stating rather than
 * quietly breaking. `singleParent()` assigns ONE parent per TABLE — the first
 * name-prefix match whose primary key is a subset of the child's — and after Phase 0
 * that first match is the tenant FK on all 261 tables. Its own docblock says the
 * consequence: "the `soft` branch is dead code on this tree and 217 tables' real
 * intra-mirror edges are never examined". Discovering through it would find NONE of
 * these six. They are invisible to the discovery rule by construction.
 *
 * So the anti-drift mechanism for this family is the GATE, not the discovery: the
 * gate enumerates by COLUMN name, independently of `singleParent()`, names every
 * unconstrained site on every run, and fails. Adding a seventh
 * `belongsTo(parent, column, 'id')` to a generated model does not need a migration
 * to catch it — it needs the gate, which already does. This list is the reviewed,
 * bounded part; the gate is the general part.
 *
 * ## ON DELETE CASCADE IS DERIVED, NOT CHOSEN PER TABLE
 *
 * One rule for the whole set, in one place, for the reason the messages and chats
 * families give: these are child facts of a peer row with no life outside it. A peer
 * re-delivered with a shorter field vector must take its old children with it, or the
 * dropped ones are orphaned under a re-placed parent.
 *
 * ## NOTHING HERE READS A VALUE
 *
 * `access_hash`-shaped and token-shaped columns are a real concern in this schema and
 * this migration never reads, writes, logs or migrates one: it declares a structural
 * constraint over column NAMES. `php bin/scan-credentials.php` covers the tree.
 */
return new class extends Migration
{
    private const CONSTRAINT_SUFFIX = '_declared_parent_fk';

    /**
     * The six, each with the reason it is here rather than in a later wave.
     *
     * @var list<array{child: string, column: string, parent: string, why: string}>
     */
    private const RELATIONS = [
        [
            'child' => 'tf_channel_updates',
            'column' => 'channel_id',
            'parent' => 'tf_channels',
            'why' => 'belongsTo(TlChannel::class, \'channel_id\', \'id\') with no constraint behind it',
        ],
    ];

    /**
     * THE FOUR THAT CANNOT TAKE A CONSTRAINT, and the proofs, pasted.
     *
     * `tf_updates` carries `user_id`, `chat_id` and `channel_id` as three separate
     * columns and each is 0 when Telegram did not send that peer. A FOREIGN KEY
     * refuses 0, so declaring one turns every peer-less update into a failed
     * insert. MEASURED, with this migration carrying all three:
     *
     *   Illuminate\Database\QueryException: SQLSTATE[23000]: Integrity constraint
     *   violation: 19 FOREIGN KEY constraint failed
     *   (SQL: insert into "tf_updates" ("account_id", "seq", "position",
     *    "constructor") values (4242, -997799901, 3056, updateNewMessage))
     *
     * Ten tests in tests/Ingest errored on exactly that statement. The columns are
     * absent from the INSERT and present as 0, which is why the FK fires rather
     * than passing on NULL.
     *
     * `tf_channel_participants.user_id` is the same class of absence by a
     * different route, and it was caught by the MySQL track rather than by me —
     * the first constraint in this file that the local suite could not refute:
     *
     *   MeRezaRezaei\Teleframe\Tests\MySql\TfChannelParticipantsFamilyFkOnMysqlTest
     *     ::test_a_valid_child_lands_and_an_orphan_is_refused_with_mysql_error_1452
     *   Illuminate\Database\QueryException: SQLSTATE[23000]: Integrity constraint
     *   violation: 1452 Cannot add or update a child row: a foreign key constraint
     *   fails (`teleframe_mysql_track`.`tf_channel_participants`, CONSTRAINT
     *   `tf_channel_participants_declared_parent_fk` FOREIGN KEY (`account_id`,
     *   `user_id`) REFERENCES `tf_users` (`account_id`, `id`))
     *     (SQL: insert into `tf_channel_participants` (`account_id`, `channel_id`,
     *      `user_id`, `constructor`, `date`) values (1724372757, 777000111, 5150,
     *      channelParticipant, 1700000000))
     *
     * `channelParticipant#1bd54456 ... user_id:long ...` names a user that Telegram
     * has NOT necessarily mirrored yet. The participant arrives; the user row does
     * not. The column tolerates that today, so a constraint cannot land on it.
     *
     * SO THE MODELS ARE WHAT OVER-PROMISE, NOT THE SCHEMA THAT REFUSES.
     * `TfUpdates` and `TfChannelParticipants` both declare a `belongsTo` for a
     * column that is 0 — or absent — on exactly the rows that make the absence
     * normal, so the relation answers null there. Fixing that means fixing the
     * MODEL: the relation should be announced as what it is. This file does not
     * pretend to have, and it does not fake a constraint the engine will not take.
     *
     * @var list<array{child: string, column: string, parent: string, why: string}>
     */
    private const REFUSED_SENTINEL = [
        [
            'child' => 'tf_admin_log_events',
            'column' => 'user_id',
            'parent' => 'tf_users',
            'why' => 'PROVEN UNENFORCEABLE, by tests/Schema/Mirror/TenantAccountForeignKeyTest.php inserting user_id=501558149 with no tf_users row: `channelAdminLogEvent#1fad7a1b id:long user_id:long date:int ...` names an ACTOR Telegram has not necessarily mirrored. Absence by non-existence, not by a zero sentinel — SQLite told me nothing and the shipped suite had to.',
        ],
        [
            'child' => 'tf_channel_participants',
            'column' => 'user_id',
            'parent' => 'tf_users',
            'why' => 'PROVEN UNENFORCEABLE ON MYSQL, not guessed: the track inserts a participant whose user row does not exist, and Telegram sends `channelParticipant` carrying a user id that may not be mirrored yet. See the migration docblock.',
        ],
        [
            'child' => 'tf_updates',
            'column' => 'user_id',
            'parent' => 'tf_users',
            'why' => 'the column is 0 when the update carries no user; a FOREIGN KEY refuses 0. The model\'s belongsTo is what over-promises, not the schema that refuses.',
        ],
        [
            'child' => 'tf_updates',
            'column' => 'chat_id',
            'parent' => 'tf_chats',
            'why' => 'as user_id — 0 when Telegram did not send that peer',
        ],
        [
            'child' => 'tf_updates',
            'column' => 'channel_id',
            'parent' => 'tf_channels',
            'why' => 'as user_id — 0 when Telegram did not send that peer',
        ],
    ];

    public function up(): void
    {
        $connection = Schema::getConnection();
        $schema = $connection->getSchemaBuilder();

        foreach (self::RELATIONS as $relation) {
            if (! $schema->hasTable($relation['child']) || ! $schema->hasTable($relation['parent'])) {
                continue;
            }

            if ($this->existingConstraint($relation) !== null) {
                continue;
            }

            // The parent's own key is the reference target. A FOREIGN KEY must
            // reference a UNIQUE index, and `tf_users`/`tf_chats`/`tf_channels` are
            // tenant-scoped: `(account_id, id)` is what the earlier families already
            // reference, and referencing the bare `id` would be a cross-tenant key.
            $legs = ['account_id', $relation['column']];

            Schema::table($relation['child'], function (Blueprint $table) use ($relation, $legs): void {
                $table->foreign($legs, $relation['child'].self::CONSTRAINT_SUFFIX)
                    ->references(['account_id', 'id'])
                    ->on($relation['parent'])
                    ->cascadeOnDelete();
            });
        }
    }

    public function down(): void
    {
        $connection = Schema::getConnection();
        $schema = $connection->getSchemaBuilder();

        foreach (self::RELATIONS as $relation) {
            if (! $schema->hasTable($relation['child'])) {
                continue;
            }

            $declared = $this->existingConstraint($relation);

            if ($declared === null) {
                continue;
            }

            // Refuse rather than guess, exactly as the chats family does. This
            // child's constraints are several migrations' work — the tenant FK
            // (`_acc_tenant_fk`), any family FK, and this one — and dropping them
            // all would silently remove another migration's constraints while
            // dropping none would report a successful rollback that changed nothing.
            if (! is_string($declared['name'] ?? null) || $declared['name'] === '') {
                throw new RuntimeException(sprintf(
                    'this driver records no foreign key constraint names, so down() cannot tell which of %s\'s '
                    .'constraints belong to this migration (suffix %s) and which belong to an earlier one. '
                    .'Refusing rather than guessing. Roll back on MySQL, where constraints are named, or '
                    .'remove them by hand.',
                    $relation['child'],
                    self::CONSTRAINT_SUFFIX,
                ));
            }

            if ($declared['name'] !== $relation['child'].self::CONSTRAINT_SUFFIX) {
                continue;
            }

            Schema::table($relation['child'], function (Blueprint $table) use ($declared): void {
                $table->dropForeign($declared['name']);
            });
        }
    }

    /**
     * @param  array{child: string, column: string, parent: string, why: string}  $relation
     * @return array<string, mixed>|null
     */
    private function existingConstraint(array $relation): ?array
    {
        $legs = ['account_id', $relation['column']];

        foreach (Schema::getConnection()->getSchemaBuilder()->getForeignKeys($relation['child']) as $foreign) {
            if ((string) ($foreign['foreign_table'] ?? '') !== $relation['parent']) {
                continue;
            }

            if (array_map('strval', $foreign['foreign_columns'] ?? []) !== ['account_id', 'id']) {
                continue;
            }

            if (array_map('strval', $foreign['columns'] ?? []) !== $legs) {
                continue;
            }

            return $foreign;
        }

        return null;
    }
};
