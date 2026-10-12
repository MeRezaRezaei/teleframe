<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use MeRezaRezaei\Teleframe\Mirror\Models\TfBotInfo;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBotInfoCommand;
use MeRezaRezaei\Teleframe\Mirror\Models\TfBotInfoUserId;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfTodoLists;
use MeRezaRezaei\Teleframe\Schema\Generated\Models\TfTodoListsTitle;

/**
 * The Eloquent relation graph TRAVERSES on a real engine — proved by reading,
 * never by counting declarations.
 *
 * ## WHY THIS FILE EXISTS WHEN THE OTHER GATES ARE ALREADY GREEN
 *
 * Every other proof in this repository is a COUNT or a SHAPE:
 *
 *   - `EveryForeignKeyIsReachableThroughARelationTest` reports 167 of 460
 *     foreign-key constraints reachable and 293 deliberately open, having read
 *     DECLARATIONS — a relation method exists, points the right way, and is
 *     cardinally correct.
 *   - `MirrorRelationPlan` produces 422 relations across 265 models.
 *   - `TfBotFamilyFkOnMysqlTest` counts eighteen constraints and proves the
 *     engine ENFORCES them.
 *
 * None of those proves that FOLLOWING a relation returns the right row. That is
 * a different question with a silent failure mode, and silence is the whole
 * problem:
 *
 *   - a relation that does not exist returns `null`, and
 *   - a relation that EXISTS and is BROKEN also returns `null`.
 *
 * A caller cannot tell those apart. A broken relation therefore produces empty
 * pages and logs nothing, which is why a declaration-level gate can be fully
 * green over a graph that answers nothing. This file is the half that cannot be
 * satisfied by a method merely existing: every assertion below READS A ROW BACK
 * THROUGH A RELATION and identifies it by a column value chosen when the row was
 * written.
 *
 * ## THE ONLY RAW QUERIES IN THIS FILE ARE WRITES AND TWO CONTRASTS
 *
 * A `DB::table('tf_bot_infos')->where(...)->sole()` proves a ROW exists. The
 * question here is about the OBJECT GRAPH, so the READ is always through a
 * relation accessor. A raw SELECT appears exactly twice, both times to CONTRAST
 * — once to prove the row is genuinely there (so a `null` from a relation cannot
 * be excused as "the row was never written") and once to prove a decoy row
 * exists for a relation to wrongly return.
 *
 * ## NO ACCOUNT CONTEXT IS SET, AND THAT IS DELIBERATE
 *
 * `AccountScope` is a global scope that filters on `AccountContext::current()`,
 * and it is a documented NO-OP when no context is active. `MySqlTestCase` binds
 * no `config`, so `AccountContext::configDefault()` returns null and the scope
 * filters nothing.
 *
 * That is exactly the condition under which a cross-tenant read is reachable,
 * which makes it the condition worth testing. Every leg here — the tenant leg
 * included — is therefore carried by the RELATION itself, through
 * `PinsDeclaredForeignKeyLegs`, and the decoy rows exist to catch it if it does
 * not. Setting an `AccountContext` would make these tests green for the wrong
 * reason: the scope would filter the leak away before the relation could.
 *
 * ## WHY `tf_bot_infos` FOR TWO OF THE THREE, AND NOT A BIGGER FAMILY
 *
 * `tf_bot_infos` is a family root whose every relation is keyed on the parent's
 * OWN composite key `(account_id, id)` — so the leg set a relation must pin is
 * short, known, and readable off the engine. The fixture is four rows for the
 * 1:1 half. A family with a five-leg key would prove the same thing with a
 * fixture whose every NOT NULL column has to be got right first, and a fixture
 * that fights the schema is a fixture whose failures are about the fixture.
 *
 * ## AND THE THIRD TEST IS A RELATION THE STATIC GATE LEAVES OPEN
 *
 * `EveryForeignKeyIsReachableThroughARelationTest` files
 * `tf_todo_lists_title.(account_id, todo_list_id) -> tf_todo_lists` as
 * DELIBERATELY OPEN — the parent `tf_todo_lists` is in its
 * `PARENTS_WITHOUT_TL_PROVENANCE` set, because the TL has no `todoList`
 * constructor for this table to be checked against. "Open" in that gate means
 * the gate could not find a relation it RECOGNISES. It does not mean no
 * relation is declared.
 *
 * The relation IS declared, on both sides:
 * `TfTodoLists::todoListsTitle()` (`src/Schema/Generated/Models/TfTodoLists.php:64`)
 * and `TfTodoListsTitle::todoLists()`
 * (`src/Schema/Generated/Models/TfTodoListsTitle.php:59`). The gate's
 * `MODEL_DIRECTORIES` does not include `src/Schema/Generated/Models/`, so it
 * never loads either class.
 *
 * So this is a claim the static gates can neither confirm nor deny, and it is
 * decided here by reading a row back. The honest limit is stated in the test
 * body itself: `2026_09_14_200050_create_tf_misc_tables.php:166-170` records
 * that no writer can produce a `tf_todo_lists_title` row because the parent's
 * `todo_list_id` is synthetic and unfillable. THIS PROVES THE RELATION
 * TRAVERSES GIVEN A ROW. It does not prove a row is ever written.
 *
 * ## AND EVERY ASSERTION HERE HAS BEEN SEEN RED
 *
 * A traversal test that has never failed is not evidence. Each of the three
 * was run against a deliberately BROKEN copy of its relation, under `/tmp`, and
 * each went red. The scratch copies and their verbatim failure output are
 * recorded in `.brain/decisions/D-030-g4-traversal-negative-controls.md`.
 */
final class RelationGraphTraversesOnMysqlTest extends MySqlTestCase
{
    /** The tenant every `tf_*` row hangs off. A real-looking Telegram user id. */
    private const ACCOUNT_A = 611000001;

    /** A SECOND tenant. Decoy rows live here, so a leak has somewhere to go. */
    private const ACCOUNT_B = 611000002;

    /** `tf_bot_infos.id` — the bot's own user id, the family root's key half. */
    private const BOT_ONE = 900001;

    /** A SIBLING bot under the SAME tenant: same `account_id`, different `id`. */
    private const BOT_SIBLING = 900002;

    /**
     * The SAME `id` as `BOT_ONE`, under the OTHER tenant.
     *
     * This is the decoy that makes the tenant leg observable. `tf_bot_infos` is
     * keyed `(account_id, id)` and its child constraint is
     * `(account_id, id) -> tf_bot_infos(account_id, id)`, so this row is legal
     * and a relation that pinned only `id` would return it.
     */
    private const BOT_DECOY = 900001;

    /** The user id written into `tf_bot_infos_user_id.user_id`, and asserted on read-back. */
    private const BOT_USER_ID = 900001;

    /** `tf_todo_lists.todo_list_id`, shared by the tenant-A row and its decoy. */
    private const TODO_LIST_ID = 7;

    protected function setUp(): void
    {
        parent::setUp();

        // Plant both tenants. `telegram_accounts.user_id` is what every
        // `account_id` foreign key REFERENCES (not `id`, and not
        // `account_id`), so it must be non-null and unique.
        $db = $this->migratedMysqlConnection();

        $db->table('telegram_accounts')->insert([
            [
                'label' => 'g4-tenant-a',
                'type' => 'user',
                'user_id' => self::ACCOUNT_A,
            ],
            [
                'label' => 'g4-tenant-b',
                'type' => 'user',
                'user_id' => self::ACCOUNT_B,
            ],
        ]);
    }

    /**
     * 1 — hasOne / belongsTo ROUND-TRIP.
     *
     * A real `tf_bot_infos` row, a real `tf_bot_infos_user_id` row, traversed in
     * BOTH directions and identified by `user_id` — the value chosen at write
     * time, not a row count and not the child's own primary key (which the
     * parent already had to supply to insert it, so agreeing on it would prove
     * nothing about the relation matching).
     *
     * THE SILENT FAILURE IT CATCHES: a `hasOne` declared against a column the
     * row does not carry, or built on a key that cannot match. Both return
     * `null`. A test that asserted only "not null" would be vacuous; one that
     * asserted a count would pass on an unrelated row. Asserting the VALUE is
     * what makes a wrong-but-non-empty result fail too.
     */
    public function test_a_declared_has_one_and_its_belongs_to_return_the_row_that_was_written(): void
    {
        $db = $this->migratedMysqlConnection();

        $db->table('tf_bot_infos')->insert(['account_id' => self::ACCOUNT_A, 'id' => self::BOT_ONE]);
        $db->table('tf_bot_infos_user_id')->insert([
            'account_id' => self::ACCOUNT_A,
            'id' => self::BOT_ONE,
            'user_id' => self::BOT_USER_ID,
        ]);

        // CONTRAST 1: the row really is there, so a `null` from the relation
        // below cannot be excused as "the row was never written".
        $raw = $db->table('tf_bot_infos_user_id')
            ->where('account_id', self::ACCOUNT_A)
            ->where('id', self::BOT_ONE)
            ->first();
        self::assertNotNull($raw, 'the fixture row is missing; the read-back below would prove nothing');
        self::assertSame(self::BOT_USER_ID, (int) $raw->user_id);

        $bot = TfBotInfo::query()
            ->where('account_id', self::ACCOUNT_A)
            ->where('id', self::BOT_ONE)
            ->first();

        self::assertNotNull($bot, 'the parent fact row is not readable');

        // parent -> child, through the DECLARED hasOne.
        //
        // The relation is FETCHED ONCE into a variable and every assertion below
        // reads that one value. Calling `first()` and then `pluck()` on the same
        // relation object would share one builder, and the `limit 1` the first
        // call applies would silently narrow the second — which is exactly the
        // kind of quiet wrong-answer this file exists to rule out, and it bit
        // this very test's first draft.
        $userId = $bot->userId()->first();
        self::assertNotNull(
            $userId,
            'TfBotInfo::userId() returned no row. The row exists (proved above), so the relation '
            .'is broken — which is indistinguishable from "no row" for every caller in src/.',
        );
        self::assertInstanceOf(TfBotInfoUserId::class, $userId);
        self::assertSame(
            self::BOT_USER_ID,
            (int) $userId->user_id,
            'the hasOne resolved to a row, but not the one that was written.',
        );

        // child -> parent, through the DECLARED belongsTo. A round trip: if
        // either relation pinned a leg wrongly, one direction returns the wrong
        // row while the other still returns the right one.
        $back = $userId->botInfo()->first();
        self::assertNotNull($back, 'TfBotInfoUserId::botInfo() resolved to no row on a parent that exists');
        self::assertSame(self::BOT_ONE, (int) $back->id);
        self::assertSame(self::ACCOUNT_A, (int) $back->account_id);
    }

    /**
     * 2 — hasMany COLLECTION CORRECTNESS, and no leak from a sibling parent.
     *
     * Three rows under one parent, TWO under a sibling parent under the SAME
     * tenant, and ONE under the SAME bot id in the OTHER tenant. The collection
     * must be exactly the three, identified by their `command` text.
     *
     * THE SILENT FAILURE IT CATCHES: the collection returning a sibling's rows,
     * or another tenant's. That is the failure that produces CROSS-TENANT DATA,
     * and it is silent in the worst way — the page renders, with the wrong
     * rows, and nothing throws. The assertion is by identity (the `command`
     * values) rather than by count, because a count of 5 would pass a test that
     * only checked "not empty" and would FAIL a test that checked "3" only if
     * the wrong three came back — which is why the exact set is asserted, not
     * the size.
     */
    public function test_a_declared_has_many_returns_exactly_its_own_children_and_no_other_parents(): void
    {
        $db = $this->migratedMysqlConnection();

        $db->table('tf_bot_infos')->insert([
            ['account_id' => self::ACCOUNT_A, 'id' => self::BOT_ONE],
            ['account_id' => self::ACCOUNT_A, 'id' => self::BOT_SIBLING],
            ['account_id' => self::ACCOUNT_B, 'id' => self::BOT_DECOY],
        ]);

        $commands = [];
        foreach ([[0, 'start'], [1, 'help'], [2, 'about']] as [$position, $text]) {
            $commands[] = [
                'account_id' => self::ACCOUNT_A,
                'id' => self::BOT_ONE,
                'position' => $position,
                'command' => $text,
                'description' => $text.' description',
            ];
        }
        foreach ([[0, 'sibling-a'], [1, 'sibling-b']] as [$position, $text]) {
            $commands[] = [
                'account_id' => self::ACCOUNT_A,
                'id' => self::BOT_SIBLING,
                'position' => $position,
                'command' => $text,
                'description' => $text.' description',
            ];
        }
        // Same `id` as BOT_ONE, other tenant. A relation pinning only `id`
        // would return this row and it would be indistinguishable from a real
        // command.
        $commands[] = [
            'account_id' => self::ACCOUNT_B,
            'id' => self::BOT_DECOY,
            'position' => 0,
            'command' => 'decoy-tenant-b',
            'description' => 'decoy-tenant-b description',
        ];

        $db->table('tf_bot_infos_commands')->insert($commands);

        // CONTRAST 2: every decoy really exists, so a leak below is a leak and
        // not an absence.
        $total = (int) $db->table('tf_bot_infos_commands')->count();
        self::assertSame(6, $total, 'the fixture rows are missing; the read-back below would prove nothing');

        $bot = TfBotInfo::query()
            ->where('account_id', self::ACCOUNT_A)
            ->where('id', self::BOT_ONE)
            ->first();

        self::assertNotNull($bot, 'the parent fact row is not readable');

        // Fetched ONCE. `commands()` returns a HasMany whose builder is SHARED, so
        // calling `first()` and then `pluck()` on the SAME object lets the
        // `limit 1` the first call applied silently narrow the second. That is
        // exactly the quiet wrong-answer this file exists to rule out, and it
        // bit this test's first draft: the collection reported one row where
        // three had been written, with no error anywhere.
        $collection = $bot->commands()->get();
        self::assertInstanceOf(TfBotInfoCommand::class, $collection->first(), 'the collection is empty');

        $seen = $collection->pluck('command')->map(static fn ($c): string => (string) $c)->sort()->values()->all();

        self::assertSame(
            ['about', 'help', 'start'],
            $seen,
            'the hasMany returned rows that are not exactly this parent\'s three. '
            .'A sibling under the SAME tenant or a row under the OTHER tenant has leaked in.',
        );

        // The exact set above is the primary assertion; this is the structural
        // half, and it names the leg that was dropped if the set is wrong.
        foreach ($collection as $row) {
            self::assertSame(self::ACCOUNT_A, (int) $row->account_id, 'a row from another tenant is in the collection');
            self::assertSame(self::BOT_ONE, (int) $row->id, "the row for command '{$row->command}' hangs under another bot");
        }

        self::assertCount(3, $collection, 'the collection size disagrees with its contents');
    }

    /**
     * 3 — A RELATION THE STATIC REACHABILITY GATE LEAVES DELIBERATELY OPEN.
     *
     * `EveryForeignKeyIsReachableThroughARelationTest` cannot see this relation:
     * `tf_todo_lists` is in its `PARENTS_WITHOUT_TL_PROVENANCE` set, and its
     * `MODEL_DIRECTORIES` does not include `src/Schema/Generated/Models/`, where
     * both sides of this relation are declared. The gate files the key as
     * deliberately open; that is a statement about what the gate RECOGNISES,
     * not about what is declared.
     *
     * Decided here by reading: a `tf_todo_lists` row and its title child under
     * tenant A, plus a DECOY `tf_todo_lists` row with the SAME `todo_list_id`
     * under tenant B. Both parents and both children are legal rows — the
     * composite key is `(account_id, todo_list_id)` — so a relation keyed on
     * `todo_list_id` alone returns the wrong tenant's title without raising.
     *
     * THE SILENT FAILURE IT CATCHES: exactly the cross-tenant read, on a key the
     * static gates are structurally unable to comment on.
     *
     * THE HONEST LIMIT, stated rather than discovered later: this proves the
     * RELATION TRAVERSES GIVEN A ROW. It does not prove any writer produces
     * such a row — `2026_09_14_200050_create_tf_misc_tables.php:166-170` records
     * that `tf_todo_lists_title` is unreachable from the wire because the
     * parent's `todo_list_id` is synthetic and unfillable. A relation that
     * works on a row nothing writes is still a relation that works, and it is
     * still the only evidence obtainable about this key.
     */
    public function test_a_relation_the_static_gate_leaves_open_still_traverses_and_stays_in_its_tenant(): void
    {
        $db = $this->migratedMysqlConnection();

        $db->table('tf_todo_lists')->insert([
            ['account_id' => self::ACCOUNT_A, 'todo_list_id' => self::TODO_LIST_ID],
            // Same `todo_list_id`, other tenant. Legal: the key is
            // (account_id, todo_list_id), and it is exactly the row a relation
            // keyed on `todo_list_id` alone would return.
            ['account_id' => self::ACCOUNT_B, 'todo_list_id' => self::TODO_LIST_ID],
        ]);
        $db->table('tf_todo_lists_title')->insert([
            [
                'account_id' => self::ACCOUNT_A,
                'todo_list_id' => self::TODO_LIST_ID,
                'title' => 'tenant-a-list',
                // `tf_todo_lists_title` carries BOTH `title` and `text`, and
                // `2026_10_10_000200_require_tf_todo_lists_title_text.php` makes
                // `text` NOT NULL with no default. Read back off this engine's
                // information_schema rather than off the create migration,
                // which predates the column — an insert that omits it is refused
                // with error 1364, which is how this fixture was corrected.
                'text' => 'tenant-a-list',
            ],
            [
                'account_id' => self::ACCOUNT_B,
                'todo_list_id' => self::TODO_LIST_ID,
                'title' => 'tenant-b-list',
                'text' => 'tenant-b-list',
            ],
        ]);

        $parent = TfTodoLists::query()
            ->where('account_id', self::ACCOUNT_A)
            ->where('todo_list_id', self::TODO_LIST_ID)
            ->first();

        self::assertNotNull($parent, 'the tf_todo_lists fixture row is not readable');

        $titles = $parent->todoListsTitle()->get();
        self::assertNotNull($titles->first(), 'TfTodoLists::todoListsTitle() returned no row');

        self::assertSame(
            ['tenant-a-list'],
            $titles->pluck('title')->map(static fn ($t): string => (string) $t)->all(),
            'the open-keyed relation returned the OTHER tenant\'s title. It resolved to '
            .'something, which is why a count- or not-null assertion would have passed.',
        );

        // And back up the other declared side of the same open key.
        $child = TfTodoListsTitle::query()
            ->where('account_id', self::ACCOUNT_A)
            ->where('todo_list_id', self::TODO_LIST_ID)
            ->first();

        self::assertNotNull($child, 'the tf_todo_lists_title fixture row is not readable');

        $back = $child->todoLists()->first();
        self::assertNotNull($back, 'TfTodoListsTitle::todoLists() resolved to no row on a parent that exists');
        self::assertSame(self::ACCOUNT_A, (int) $back->account_id);
    }

    /**
     * The harness contract every test above relies on, asserted once so a
     * broken harness cannot make them green for the wrong reason.
     *
     * `MySqlTestCase` already asserts `foreign_key_checks = 1`. This adds the
     * other half these tests need: that no `AccountContext` is filtering the
     * decoys away before a relation could return one. If a context WERE active,
     * the cross-tenant assertions would be green for the wrong reason.
     */
    public function test_no_account_context_is_filtering_the_decoys_away(): void
    {
        $db = $this->migratedMysqlConnection();

        $db->table('tf_bot_infos')->insert(['account_id' => self::ACCOUNT_B, 'id' => self::BOT_DECOY]);

        // Both tenants' rows are visible to a plain query in this harness.
        self::assertSame(
            1,
            (int) $db->table('tf_bot_infos')->where('id', self::BOT_DECOY)->count(),
            'the decoy parent row is not visible to a raw query',
        );
        self::assertSame(
            1,
            TfBotInfo::query()->where('account_id', self::ACCOUNT_B)->where('id', self::BOT_DECOY)->count(),
            'no AccountContext is active, so every cross-tenant assertion above is attributable '
            .'to the RELATION and not to a global scope filtering the decoy out.',
        );
    }
}
