<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;

/**
 * The enforcement harness EATS — twice: once on the pragma, once on a real
 * constraint.
 *
 * ## WHAT THIS FILE IS FOR
 *
 * `ShippedStoreSchema::capsuleFor()` builds every mirror table on a sqlite
 * `:memory:` connection with `PRAGMA foreign_keys` at SQLite's own default,
 * which is OFF. The plans call that a blind spot which "let three real defects
 * ship green" — quoted, not endorsed; `RelationConstraintTestCase`'s docblock
 * records exactly why the tree does not support the causation. What this file
 * does establish, by execution, is the narrow claim that matters: a negative
 * assertion written under that default cannot fail for the reason it claims,
 * because the constraint it names may be inert.
 *
 * So a gate that cannot fail is not a gate. These two tests are the evidence
 * that `RelationConstraintTestCase::enforcingConnection()` can actually fail:
 *
 *  1. `test_the_harness_can_turn_the_pragma_on` — the pragma reads 0 on the
 *     shipped default and 1 through the harness, on the SAME migration set.
 *  2. `test_the_pragma_decides_whether_an_orphan_row_is_rejected` — the SAME
 *     orphan insert is run twice, on two freshly-migrated databases. It
 *     SUCCEEDS under the shipped default and RAISES under the harness. If the
 *     harness were unconditionally permissive it would raise in both passes,
 *     and if the shipped default ever gained enforcement it would succeed in
 *     neither.
 *
 * ## THE PLAN'S NAMED TABLE IS ALREADY GREEN FOR THE WRONG REASON
 *
 * Task 1 of `docs/superpowers/plans/2026-10-04-foreign-key-structure.md`
 * (the IMPLEMENTATION plan — the argument lives in the sibling
 * `...-structure-plan.md`, and the Task 1 brief is NOT in that one) gates this
 * work on an orphan insert into `tf_messages_reply_markup_rows` raising,
 * calling it "one of the 53 already-constrained tables". Two things are wrong
 * with that, and only the first is the obvious one:
 *
 *  1. **The table declares ZERO foreign keys.** Not in
 *     `2026_09_14_200010_create_tf_messages_tables.php:644-656` (it is
 *     created with a primary key and nothing else) and not in
 *     `2026_09_14_299999_create_tf_foreign_keys.php`, which constrains only
 *     the CHILD `tf_messages_reply_markup_rows_buttons` (`:98-111`). It is
 *     the PARENT half of `tf_reply_markup_buttons_row_fk` — referenced,
 *     never referencing. So the attribution is wrong: with a fixture-valid
 *     row the orphan insert SUCCEEDS even with the pragma ON.
 *  2. **THE PLAN'S OWN CODE IS GREEN TODAY, AND NOT BECAUSE OF A FOREIGN
 *     KEY.** This is the worse half, and it is what
 *     `test_the_plans_own_gate_is_green_only_because_of_a_missing_column()`
 *     pins. The row Task 1 inserts carries a `parent_position` column
 *     (`2026-10-04-foreign-key-structure.md:155-158`) that this table does not
 *     have — `parent_position` belongs to the BUTTONS table. So the insert
 *     raises `QueryException`, the plan's `expectException(QueryException::class)`
 *     is satisfied, and the gate passes without any constraint ever being
 *     consulted. Run verbatim, both of Task 1's tests are green.
 *
 * Task 1's own Step 2 (`:163-170`) anticipated only the opposite failure — "if
 * the second test does not raise… that is a finding" — so this second half
 * was nobody's expected case. It is the exact disease this file exists to
 * name: `expectException(QueryException::class)` alone cannot tell a
 * constraint from a typo, which is why every behavioural assertion below
 * matches on the driver message.
 *
 * MEASURED (not read off the plan), executing the shipped migrations through
 * `ShippedStoreSchema::capsuleFor()` and reading `Schema::getForeignKeys()`:
 *
 *   tf_messages_reply_markup_rows            : 0 FK(s)
 *   tf_messages_reply_markup_rows_buttons    : 1 FK  cols=[account_id,peer_type,peer_id,id,parent_position]
 *                                                    -> tf_messages_reply_markup_rows  CASCADE
 *   tf_messages_entities                     : 1 FK  cols=[account_id,peer_type,peer_id,id]
 *                                                    -> tf_messages  CASCADE
 *
 * and the behavioural consequence, a fresh capsule per case, orphan row in
 * `tf_messages_reply_markup_rows` (account_id 41, peer_type 1, peer_id
 * 501558149, id 7001, position 0):
 *
 *   PRAGMA foreign_keys = OFF  ->  INSERT SUCCEEDED
 *   PRAGMA foreign_keys = ON   ->  INSERT SUCCEEDED      <- no constraint to consult
 *
 * while the two children that DO declare a constraint:
 *
 *   PRAGMA foreign_keys = OFF  ->  INSERT SUCCEEDED
 *   PRAGMA foreign_keys = ON   ->  RAISED QueryException:
 *                                 SQLSTATE[23000]: Integrity constraint violation:
 *                                 19 FOREIGN KEY constraint failed
 *
 * THAT THE PLAN'S COUNTS ARE RIGHT IS NOT IN DISPUTE. Executing the WHOLE
 * curated dial (all 34 shipped migration files) and asking the built schema,
 * in one pass:
 *
 *   ALL tables ................................ 271   (266 are tf_*)
 *   tf_* tables ............................... 266
 *   tf_* tables with at least one FOREIGN KEY ..  53   <- the plan's "53"
 *   total FK constraints on tf_* tables .......  54   (53 CASCADE + 1 RESTRICT)
 *   tf_messages_reply_markup_rows in that 53? . false
 *
 * So the total is correct and the attribution is not: this table is not one of
 * the 53. It is a Phase 1 TARGET, not a Phase 1 precedent —
 * `tf_messages_reply_markup_rows` -> `tf_messages` is one of the 119
 * single-parent soft links the plan exists to constrain. So the tests below
 * assert on the two tables that genuinely declare a constraint, and assert that
 * declaration from the BUILT schema first, so a later migration that drops the
 * constraint fails here loudly instead of quietly going back to being a
 * decoration.
 *
 * ## WHY THE DECLARATION IS ASSERTED BEFORE THE BEHAVIOUR
 *
 * A negative test alone cannot distinguish "the constraint bites" from "this
 * table never had a constraint" — nor from "the insert was malformed". Task 1's
 * gate is the second failure wearing the first's clothes. Reading
 * `Schema::getForeignKeys()` off the executed DDL first closes the first:
 * the test proves the constraint EXISTS, then proves it FIRES, and deleting
 * either proof turns the suite red.
 */
final class RelationConstraintsAreEnforcedTest extends RelationConstraintTestCase
{
    /**
     * The minimum shipped migration set that produces a database containing
     * both genuinely-constrained children AND their parents, plus the FK
     * migration that declares the constraints.
     *
     * Named in apply order; nothing about any table shape is transcribed, so
     * this list cannot drift from the DDL the way a hand-built schema would.
     * Verified by execution — this exact list migrates clean on sqlite.
     *
     * @var list<string>
     */
    private const MIGRATIONS = [
        '2026_09_14_200003_create_tf_channels_tables.php',
        '2026_09_14_200010_create_tf_messages_tables.php',
        '2026_09_14_200011_create_tf_message_entities_tables.php',
        '2026_09_14_200012_create_tf_message_medias_tables.php',
        '2026_09_14_200031_create_tf_channel_participants_tables.php',
        '2026_09_14_299999_create_tf_foreign_keys.php',
    ];

    private const ACCOUNT = 41;

    /** A dialog. `tf_messages` is peer-scoped, so every fixture needs a real one. */
    private const PEER_TYPE = 1;

    private const PEER_ID = 501558149;

    /**
     * The two children whose orphan rows are used below, each with the exact
     * FK the executed schema declares for it.
     *
     * Measured with `Schema::getForeignKeys()` on the migrated schema — see
     * the file docblock for the verbatim read-back.
     *
     * @var array<string, array{columns: list<string>, parent: string}>
     */
    private const CONSTRAINED_CHILDREN = [
        'tf_messages_entities' => [
            'columns' => ['account_id', 'peer_type', 'peer_id', 'id'],
            'parent' => 'tf_messages',
        ],
        'tf_messages_reply_markup_rows_buttons' => [
            'columns' => ['account_id', 'peer_type', 'peer_id', 'id', 'parent_position'],
            'parent' => 'tf_messages_reply_markup_rows',
        ],
    ];

    /**
     * The plan's named table, carried as a NEGATIVE control rather than
     * deleted: it is the measured counter-example for "a declared FK and a
     * behavioural FK rejection are the same thing", and the reason this file
     * checks the declaration before trusting the behaviour.
     *
     * @var list<string>
     */
    private const PLAN_NAMED_TABLE = ['tf_messages_reply_markup_rows'];

    /**
     * The row Task 1 of the implementation plan inserts, transcribed VERBATIM
     * from `docs/superpowers/plans/2026-10-04-foreign-key-structure.md:155-158`
     * — including the `parent_position` column, which is the defect.
     *
     * Transcribing a FIXTURE is exactly what `ShippedStoreSchema` exists to
     * avoid, and it is done here for one reason only: this test's subject IS
     * the transcribed fixture. Correcting the transcription would retire the
     * finding instead of pinning it. The columns it should have named are
     * asserted against the built schema on either side of it.
     *
     * @var array<string, mixed>
     */
    private const PLAN_GATE_ROW = [
        'account_id' => 1,
        'peer_type' => 2,
        'peer_id' => 900,
        'id' => 5,
        'parent_position' => 0,
        'position' => 0,
        'constructor' => 'keyboardButton',
    ];

    /**
     * An orphan row for `tf_messages_entities`: names message id 7003, which
     * no `tf_messages` row in a freshly migrated database carries.
     *
     * @return array<string, mixed>
     */
    private function orphanEntity(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => 7003,
            'position' => 0,
            'constructor' => 'messageEntityBold',
            'offset' => 0,
            'length' => 1,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orphanButton(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => 7002,
            'position' => 0,
            'parent_position' => 0,
            'constructor' => 'keyboardButton',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function orphanMarkupRow(): array
    {
        return [
            'account_id' => self::ACCOUNT,
            'peer_type' => self::PEER_TYPE,
            'peer_id' => self::PEER_ID,
            'id' => 7001,
            'position' => 0,
            'constructor' => 'keyboardButtonRow',
        ];
    }

    /**
     * @return list<string>
     */
    private function migrations(): array
    {
        return array_map(
            static fn (string $file): string => dirname(__DIR__, 3).'/src/Laravel/Migrations/'.$file,
            self::MIGRATIONS,
        );
    }

    /**
     * PROOF 1 — the pragma moves, and it is the pragma that moves it.
     *
     * Both connections come from the SAME migration set through the SAME
     * helper, so the only difference between the two reads is the one line
     * `enforcingConnection()` adds. Without the second read there is nothing
     * here that could fail on a harness that does not work.
     */
    public function test_the_harness_can_turn_the_pragma_on(): void
    {
        $shippedDefault = $this->shippedDefaultConnection();
        self::assertSame(
            0,
            $this->foreignKeysPragma($shippedDefault),
            'the shipped default must still be OFF. If this reads 1, sqlite (or ShippedStoreSchema) '
            .'changed and this whole harness is redundant — re-measure before deleting anything.',
        );

        $enforcing = $this->enforcingConnection(...$this->migrations());
        self::assertSame(
            1,
            $this->foreignKeysPragma($enforcing),
            'enforcingConnection() returns a connection whose enforcement it has already read back and '
            .'asserted; this assertion is the outward-facing half of that, so a caller can see the state '
            .'it was handed without trusting the helper.',
        );

        // And it is per-connection, not a process-wide side effect: the
        // default connection built above is a DIFFERENT capsule and must still
        // be off. A harness that leaked its pragma into the next test would
        // make an unrelated suite start failing on rows it never questioned.
        self::assertSame(
            0,
            $this->foreignKeysPragma($shippedDefault),
            'the pragma must not leak: the first capsule was built before the harness ran and must still '
            .'read OFF. A leaked pragma turns every later suite into an accidental FK test.',
        );
    }

    /**
     * PROOF 2 — the SAME insert, twice, on two freshly migrated databases.
     *
     * Under the shipped default it must SUCCEED. Under the harness it must
     * RAISE. Both halves are load-bearing, and they fail in OPPOSITE
     * directions, which is what makes this a two-sided proof:
     *  - a harness that never flipped the pragma would let the second insert
     *    through, and `self::fail()` below fires naming exactly that;
     *  - a harness that flipped nothing but somehow RAISED would satisfy the
     *    raise half alone, and the shipped-default half above is what proves
     *    the raise came from the pragma rather than from the fixture;
     *  - if the shipped default ever gains enforcement on its own, the first
     *    half fails — correctly, because a default that silently enforces
     *    would invalidate every other suite that assumed it did not.
     */
    public function test_the_pragma_decides_whether_an_orphan_row_is_rejected(): void
    {
        foreach ([
            'tf_messages_entities' => $this->orphanEntity(),
            'tf_messages_reply_markup_rows_buttons' => $this->orphanButton(),
        ] as $table => $orphan) {
            $permissive = $this->shippedDefaultConnection();
            $permissive->table($table)->insert($orphan);
            self::assertSame(
                1,
                $permissive->table($table)->count(),
                "{$table}: the orphan row must actually LAND under the shipped pragma-off default — this "
                .'connection is the control that proves the next connection is the thing that rejected it, '
                .'rather than a fixture that failed for an unrelated reason.',
            );

            $enforcing = $this->enforcingConnection(...$this->migrations());
            try {
                $enforcing->table($table)->insert($orphan);
                self::fail(
                    "{$table}: an orphan row with no parent was ACCEPTED on a connection whose "
                    .'PRAGMA foreign_keys was read back as 1. Either the constraint is not really declared '
                    .'or enforcement is not really on, and both mean this suite is not a gate.',
                );
            } catch (QueryException $e) {
                // Assert the message is the CONSTRAINT and not an incidental
                // failure: `expectException(QueryException::class)` alone
                // accepts "no such column", which is a fixture bug wearing a
                // gate's clothes.
                self::assertStringContainsString(
                    'FOREIGN KEY constraint failed',
                    $e->getMessage(),
                    "{$table}: the insert must be refused BY THE FOREIGN KEY. Any other QueryException "
                    .'means the fixture is wrong, not that the constraint bit.',
                );
            }

            self::assertSame(
                0,
                $enforcing->table($table)->count(),
                "{$table}: nothing may be left behind by the refused insert.",
            );
        }
    }

    /**
     * The declaration is asserted from the EXECUTED DDL, so the behavioural
     * proofs above cannot be satisfied by a table that never had a constraint.
     *
     * This is the assertion the plan's Phase 1 negative tests need and do not
     * have: it turns "the insert raised" into "the insert raised BECAUSE this
     * constraint exists, and here are its exact legs".
     */
    public function test_each_proven_child_really_declares_the_constraint_it_claims(): void
    {
        $db = $this->enforcingConnection(...$this->migrations());

        foreach (self::CONSTRAINED_CHILDREN as $child => $expected) {
            $declared = $db->getSchemaBuilder()->getForeignKeys($child);
            self::assertCount(
                1,
                $declared,
                "{$child} must declare exactly one cross-domain foreign key; the orphan-rejection proof "
                .'above is only meaningful while this holds.',
            );

            self::assertSame(
                $expected['columns'],
                array_map('strval', $declared[0]['columns']),
                "{$child}: the FK legs are the whole identity, peer-scoped — Telegram numbers messages per "
                .'chat, so an id-only leg is not unique within an account. Read back from the executed DDL.',
            );
            self::assertSame($expected['parent'], (string) $declared[0]['foreign_table']);
            self::assertSame(
                'CASCADE',
                strtoupper((string) $declared[0]['on_delete']),
                "{$child}: a positioned vector child cascades with its parent row — it has no life outside it.",
            );
        }
    }

    /**
     * THE MEASURED COUNTER-EXAMPLE, kept as a test rather than a footnote.
     *
     * The plan gates Phase 1 on `tf_messages_reply_markup_rows` raising for an
     * orphan. It does not, because it declares no constraint — it is the
     * parent half of one. This test pins that finding so it cannot drift back
     * into prose: if a later migration DOES give this table a foreign key to
     * `tf_messages`, this test goes red and the plan's claim becomes true,
     * which is exactly when the finding should be retired.
     *
     * It is a negative control for the whole file: the two children above are
     * rejected by a constraint, and this one is rejected by nothing, on the
     * same connection, in the same schema.
     */
    public function test_the_plan_named_table_declares_no_constraint_and_admits_the_orphan(): void
    {
        $table = self::PLAN_NAMED_TABLE[0];

        $db = $this->enforcingConnection(...$this->migrations());

        self::assertSame(
            [],
            $db->getSchemaBuilder()->getForeignKeys($table),
            "{$table} declares no foreign key. The Task 1 brief for the foreign-key-structure plan "
            .'names it as one of the 53 already-constrained tables; MEASURED over the whole curated dial, '
            .'it is not — the 53 are real, this table is not one of them. '
            ."constrained child. If this now fails, {$table} has been given a constraint and the brief's "
            .'claim has become true — update the finding, do not delete the test.',
        );

        $db->table($table)->insert($this->orphanMarkupRow());

        self::assertSame(
            1,
            $db->table($table)->count(),
            "{$table}: on a connection whose PRAGMA foreign_keys reads 1, an orphan markup row is still "
            .'ADMITTED, because the table declares nothing to reject it. This is the shape Phase 1 has to '
            .'close — and the reason a "negative test" written against this table would have been green '
            .'without ever testing an orphan.',
        );
    }

    /**
     * THE LIVE FINDING, kept as a test rather than a footnote: the plan's own
     * Task 1 gate passes right now, for a reason that has nothing to do with
     * foreign keys.
     *
     * Its row names `parent_position`, a column that exists on the BUTTONS
     * table and not on the one it inserts into. So the insert raises
     * `QueryException`, `expectException(QueryException::class)` is satisfied,
     * and no constraint is ever consulted. `Run verbatim, both of Task 1's two
     * tests are green` — measured against
     * `docs/superpowers/plans/2026-10-04-foreign-key-structure.md:143-159`.
     *
     * This is the finding that matters, because it is still true: anyone
     * reading the plan today would run that gate, see it pass, and conclude
     * the constraint bites. It does not bite, and this test is what says so
     * in a form that cannot be argued with.
     *
     * It goes red in both directions, which is the point of pinning it: add
     * the column and this fails (the row becomes insertable, so the finding is
     * gone), and give the table a real FK while the column is still absent and
     * it fails too (the violation changes from "no such column" to a genuine
     * foreign-key rejection, so the plan's gate would finally be real).
     */
    public function test_the_plans_own_gate_is_green_only_because_of_a_missing_column(): void
    {
        $table = self::PLAN_NAMED_TABLE[0];
        $buttons = 'tf_messages_reply_markup_rows_buttons';

        $db = $this->enforcingConnection(...$this->migrations());

        // The plan's row names a column that belongs to the OTHER table.
        self::assertNotContains(
            'parent_position',
            $db->getSchemaBuilder()->getColumnListing($table),
            "{$table} has grown a parent_position column, so the plan's row is now insertable and the "
            .'finding is stale — re-measure what raises, and retire this test rather than amend it.',
        );
        self::assertContains(
            'parent_position',
            $db->getSchemaBuilder()->getColumnListing($buttons),
            "{$buttons} is the table parent_position belongs to; if it is gone, this finding is about "
            .'something else and the docblock above is wrong.',
        );

        // And the plan's gate, run verbatim, is satisfied — by the typo.
        try {
            $db->table($table)->insert(self::PLAN_GATE_ROW);
            self::fail(
                "the plan's row inserted cleanly. Its expectException(QueryException::class) gate is now "
                .'red for real rather than green by accident, which is a different finding.',
            );
        } catch (QueryException $e) {
            self::assertStringContainsString(
                'no column named parent_position',
                $e->getMessage(),
                "the plan's gate must be passing because of the misspelled column. If it raises for any "
                .'other reason, this finding is stale — record the new reason and retire this test.',
            );
            self::assertStringNotContainsString(
                'FOREIGN KEY constraint failed',
                $e->getMessage(),
                'if the plan\'s gate is already raising on the FOREIGN KEY then its premise became true and '
                .'the finding it rests on must be withdrawn.',
            );
        }

        self::assertSame(
            0,
            $db->table($table)->count(),
            "a refused insert must leave nothing behind — otherwise the plan's gate would be raising on a "
            .'row that had already landed.',
        );
    }

    /**
     * A capsule built exactly the way every other mirror test builds one:
     * shipped DDL, sqlite default, pragma untouched.
     *
     * Deliberately NOT `enforcingConnection()` — this is the control.
     */
    private function shippedDefaultConnection(): Connection
    {
        return ShippedStoreSchema::capsuleFor(...$this->migrations())
            ->getConnection();
    }
}
