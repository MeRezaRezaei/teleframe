<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use ReflectionClass;

/**
 * `tf_dialog_filters` family FK acceptance on real MySQL/InnoDB.
 *
 * ## RE-BASELINED 2026-10-05: THE HEADLINE MEASUREMENT IS NOW 7 OF 7
 *
 * ## WHAT THIS FILE USED TO CLAIM, AND WHY IT WAS TRUE
 *
 * `2026_10_04_000870` discovers 7 single-parent relations here. It wrote ONE
 * constraint `tf_dialog_filters_title_entities` → `tf_dialog_filters_title`,
 * and REFUSED the SIX depth-1 relations to `tf_dialog_filters`, because that root
 * was keyed `int` while every child was keyed `bigint`:
 *
 *     SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE
 *       FROM information_schema.COLUMNS
 *      WHERE TABLE_SCHEMA = DATABASE()
 *        AND TABLE_NAME LIKE 'tf_dialog_filters%'
 *        AND COLUMN_NAME IN ('account_id','id');
 *
 *     tf_dialog_filters                  account_id bigint unsigned   id int
 *     tf_dialog_filters_title            account_id bigint unsigned   id bigint
 *     tf_dialog_filters_title_entities   account_id bigint unsigned   id bigint
 *     tf_dialog_filters_color            account_id bigint unsigned   id bigint
 *     … (all five remaining children likewise `bigint`)
 *
 * `test_the_refused_depth_1_leg_is_refused_by_mysql_3780()` did not take that on
 * trust: it ATTEMPTED the refused ALTER on the real engine and required MySQL to
 * reject it, with a control half that the accepted depth-2 shape is not rejected.
 * That negative assertion justified the migration's `REFUSED` list, and it was an
 * assertion sqlite cannot make at all — sqlite reports `int` and `bigint`
 * with the SAME declared type, so a SQLite-only suite reports green over this
 * defect forever.
 *
 * ## WHY IT IS NOW 7 OF 7
 *
 * `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php` widened
 * `tf_dialog_filters.id` from `int` to `bigint` and landed the six depth-1
 * constraints. D-020 pre-authorized exactly this ("If `tf_dialog_filters.id` is
 * ever widened to bigint by a future migration, the 6 refusals become constrable
 * with no key change and MUST be landed then"), and its own "what would make this
 * wrong" clause said the REASON was the thing to re-check rather than the count.
 * The reason was re-checked and no longer holds.
 *
 * FOUR tests were stale in the OPPOSITE direction — they failed because the
 * constraint now EXISTS — and every one is RE-BASELINED rather than deleted:
 *
 *   - `test_the_refused_relations_are_exactly_the_integer_width_incompatible_ones`
 *     now asserts BOTH sets are empty, and keeps the "the loop actually examined
 *     something" control, because an empty incompatible set is also what a broken
 *     discovery produces.
 *   - `test_the_refused_depth_1_leg_is_refused_by_mysql_3780` still ATTEMPTS the
 *     depth-1 ALTER on the real engine but now requires MySQL to ACCEPT it, to
 *     carry the declared CASCADE, and to BITE (1452 on an orphan). Its depth-2
 *     control half is UNCHANGED, which is what keeps an acceptance attributable to
 *     the column width and not to a permissive server.
 *   - `test_depth_1_an_orphan_lands_because_the_depth_1_leg_is_refused` is now
 *     `test_depth_1_an_orphan_is_refused_because_the_depth_1_leg_now_exists`; the
 *     fabricated-tenant control half is UNCHANGED.
 *   - `test_the_hop_two_cascade_is_proven_and_the_hop_one_gap_is_recorded` no
 *     longer records a hop-one GAP, because there is no gap: deleting the filter
 *     now prunes the title AND the entities.
 *
 * Each of those four failure messages PREDICTED its own invalidation — "if
 * this row is now refused, either tf_dialog_filters has been re-keyed to bigint
 * and the whole refusal reasoning in this file and in the migration is stale".
 * That is what happened. The SQLite sibling
 * `tests/Schema/Mirror/TfDialogFiltersFamilyForeignKeyTest.php` was re-baselined
 * the same way in commit 73f654c3, and the reasoning matches it.
 *
 * ## WHY THIS LANE EXISTS AT ALL
 *
 * sqlite cannot raise 3780 (referencing/referenced types or signedness
 * incompatible), 6125 (parent key not unique over the legs), 1059 (constraint
 * name over 64 characters) or 1170 (TEXT in a PRIMARY KEY). The first three are
 * migration-time refusals — no schema is produced and the SQLite suite reports
 * green. This lane runs the whole shipped dial on MySQL 8.4.11 and then
 * exercises what actually ships on the engine.
 *
 * ## BOTH DEPTHS ARE SEPARATE, AND EACH HAS ITS OWN THREE ASSERTIONS
 *
 * This family is a two-level chain, so a single combined test would not say
 * which depth is proven:
 *
 *   - depth 1 — NOW CONSTRAINED (was REFUSED until 001100), so the assertions are
 *     that the leg is ACCEPTED by MySQL, that it carries the CASCADE it declares,
 *     and that an orphan is REFUSED with 1452 read from `$e->errorInfo[1]` (the
 *     DRIVER slot; SQLSTATE for this class of failure is the generic HY000 and
 *     proves nothing).
 *   - depth 2 — CONSTRAINED, so a valid row must LAND, an orphan must be refused
 *     with 1452 read from `$e->errorInfo[1]`, and the CONTROL HALF — the same
 *     orphan with `SET SESSION foreign_key_checks = 0` — must LAND.
 *
 * The control half is what makes the refusal evidence. A harness that was
 * unconditionally permissive raises in both passes and fails the control; a
 * constraint that simply is not there also fails the control, because the row
 * would land with the checks on.
 *
 * ## WHY THE FIXTURES PLANT A REAL TENANT AND MAKE ORPHAN-NESS LIVE ONLY IN
 * THE DIALOG-FILTER COORDINATES
 *
 * Every `tf_*` table in this family carries its own `_acc_tenant_fk` to
 * `telegram_accounts.user_id` (D-005). An orphan built by inventing an
 * `account_id` no tenant carries would be refused by the TENANT constraint and
 * every 1452 assertion below would be green while this migration contributed
 * nothing. So each fixture plants a real tenant, and the only thing wrong with
 * the orphan is the parent coordinate.
 *
 * At depth 2 the fixture is narrower still: the ROOT filter IS planted and only
 * the middle hop is missing, so the refusal cannot be attributed to the tenant
 * constraint, nor to a depth-1 constraint — there is none.
 *
 * ## WHY THE ROW SHAPES ARE NOT TYPED HERE
 *
 * A fixture that disagrees with the shipped schema fails loudly; a fixture that
 * happens to agree today silently drifts the day a column changes, and the suite
 * keeps reporting green. So every insert is checked against the EXECUTED schema
 * — `information_schema.COLUMNS` read off the engine after the dial ran — and
 * each test refuses to proceed unless its row covers every NOT NULL column that
 * has no default and no auto-increment, and names no column the engine lacks.
 */
final class TfDialogFiltersFamilyFkOnMysqlTest extends MySqlTestCase
{
    private const TENANT_ACCOUNT_ID = 4242;

    /** A `telegram_accounts.user_id` this track does NOT carry. */
    private const FABRICATED_ACCOUNT_ID = 999999;

    /** The family ROOT and the middle hop. */
    private const FILTER = 'tf_dialog_filters';

    private const TITLE = 'tf_dialog_filters_title';

    /**
     * A depth-1 relation, and the one whose refusal reason the migration spells
     * out in full: `int` parent id against a `bigint` child id.
     */
    private const REFUSED_WITNESS = 'tf_dialog_filters_color';

    /** The depth-2 witness, and this family's longest table name at 32 chars. */
    private const GRANDCHILD = 'tf_dialog_filters_title_entities';

    private const PLANTED_FILTER = 7;

    /** A filter that is NEVER planted — the orphan. */
    private const ABSENT_FILTER = 4242;

    /**
     * The path fragment naming the migration under test, for reading its
     * constants by reflection.
     *
     * A test that hardcodes the spelling it is checking cannot notice the
     * spelling change that broke something, so `REFUSED` is read out of the file
     * itself rather than transcribed.
     */
    private const MIGRATION = '2026_10_04_000870';

    /**
     * `REFUSED` read out of the migration, with its shape proven.
     *
     * @return array<string, string>
     */
    private function migrationRefused(): array
    {
        $migration = require dirname(__DIR__, 2).'/src/Laravel/Migrations/'.self::MIGRATION
            .'_add_tf_dialog_filters_family_foreign_keys.php';

        $refused = (new ReflectionClass($migration))->getConstant('REFUSED');

        self::assertIsArray($refused, 'REFUSED must be an array of child => reason');
        foreach ($refused as $child => $reason) {
            self::assertIsString($reason, "REFUSED[{$child}] must carry the REASON, not just the name");
            self::assertNotSame('', trim($reason), "REFUSED[{$child}] has an empty reason");
        }

        return $refused;
    }

    /**
     * Every single-parent relation in this family, discovered from the ENGINE —
     * not transcribed, and not read from `MirrorRelationPlan` (which the
     * migration uses, and is therefore the thing under test).
     *
     * The rule, independently implemented: a table's parent is the LONGEST
     * surviving table-name prefix whose primary key is a subset of this table's
     * own; the legs are that parent's key intersected down to the columns this
     * table actually has.
     *
     * @return list<array{child:string, parent:string, columns:list<string>}>
     */
    private function familyRelations(Connection $db): array
    {
        // 🔴 SCOPED TO THIS DATABASE, 2026-10-10 — the same defect the sibling
        // `TfChannelParticipantsFamilyFkOnMysqlTest` had, fixed there in this commit.
        // On this engine `getSchemaBuilder()->getTableListing()` returns entries for
        // EVERY schema on the server, schema-qualified (MEASURED on this host:
        // `ai_brain.cache`, `websv_football.matches`, and
        // `/tmp/llp-drill/full/restored.sqlite.cache`, a PATH that merely contains a
        // dot). Stripping everything left of the last dot imports identically-named
        // tables from OTHER databases into a list this method then READS COLUMNS AND
        // PRIMARY KEYS FROM — so it was inspecting another application's schema.
        // `MySqlTestCase::mysqlTableNames()` reads
        // `information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE()`, is scoped by
        // construction, returns bare names needing no normalisation, and excludes the
        // harness sentinel tables. Same answer `BackfillCrawlReachesMessageIdOneOnMysqlTest`
        // already adopted; reused so this lane has ONE way of asking the question.
        $tables = $this->mysqlTableNames();
        sort($tables);
        $tableSet = array_flip($tables);

        $columns = [];
        $primaryKey = [];
        foreach ($tables as $table) {
            $cols = [];
            foreach ($db->getSchemaBuilder()->getColumns($table) as $column) {
                $cols[(string) $column['name']] = true;
            }
            $columns[$table] = $cols;

            $key = [];
            foreach ($db->getSchemaBuilder()->getIndexes($table) as $index) {
                if (($index['primary'] ?? false) === true) {
                    $key = array_map('strval', $index['columns']);

                    break;
                }
            }
            $primaryKey[$table] = $key;
        }

        $family = [self::FILTER => true];
        foreach ($tables as $table) {
            if (str_starts_with($table, self::FILTER.'_')) {
                $family[$table] = true;
            }
        }

        $relations = [];
        foreach ($tables as $table) {
            if ($table === self::FILTER) {
                continue;
            }

            $parts = explode('_', $table);
            $parent = null;
            for ($i = count($parts) - 1; $i >= 1; $i--) {
                $candidate = implode('_', array_slice($parts, 0, $i));
                if (! isset($tableSet[$candidate], $family[$candidate])) {
                    continue;
                }
                if (array_diff($primaryKey[$candidate], $primaryKey[$table]) !== []) {
                    continue;
                }
                $parent = $candidate;

                break;
            }

            if ($parent === null) {
                continue;
            }

            $legs = [];
            foreach ($primaryKey[$parent] as $leg) {
                if (isset($columns[$table][$leg])) {
                    $legs[] = $leg;
                }
            }

            $relations[] = ['child' => $table, 'parent' => $parent, 'columns' => $legs];
        }

        return $relations;
    }

    /**
     * MySQL's own declared type for one column, straight from
     * `information_schema` — `bigint unsigned`, `int`, and so on.
     *
     * This is the ONLY place the integer WIDTH is observable. sqlite collapses
     * `int` and `bigint` to the same declared type, so nothing in the SQLite lane
     * can make this comparison.
     */
    private function columnType(Connection $db, string $table, string $column): ?string
    {
        $row = $db->selectOne(
            'SELECT COLUMN_TYPE AS type
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, $column],
        );

        return $row === null ? null : (string) $row->type;
    }

    // ------------------------------------------------------------------
    // DEPTH 1 — REFUSED, so the assertions are about the REFUSAL
    // ------------------------------------------------------------------

    /**
     * The refused relations are EXACTLY the integer-width-incompatible ones.
     *
     * This is the proof the migration's `REFUSED` list rests on, read off the
     * engine rather than off the create migration's source text. It compares the
     * DECLARED column type of every leg of every discovered relation — child
     * against parent, leg by leg — and asserts the incompatible set equals the
     * refused set exactly, in both directions:
     *
     *   - a relation MySQL would refuse but the migration does NOT refuse is an
     *     unconstrained relation that will break the first real install;
     *   - a relation the migration refuses for a TYPE reason but which MySQL
     *     would have accepted is a refusal that hides a different defect.
     *
     * sqlite cannot make this comparison at all: it reports `int` and `bigint`
     * with the same declared type, `integer`. That is why the SQLite gate
     * documents the width as a limitation instead of asserting it.
     */
    public function test_the_refused_relations_are_exactly_the_integer_width_incompatible_ones(): void
    {
        $db = $this->migratedMysqlConnection();

        $refused = $this->migrationRefused();

        // RE-BASELINED 2026-10-05, and this first half is UNCHANGED from the
        // original. It stays because it is still the guard that makes the rest of
        // the test non-vacuous: an EMPTY `REFUSED` would mean the migration stopped
        // filtering, so the empty width-incompatible set asserted below would
        // prove nothing.
        //
        // A first draft of this re-baseline asserted that `REFUSED` was now empty.
        // That was WRONG, and the engine corrected it: `REFUSED` is a filter on
        // `000870`'s OWN loop, inside a SHIPPED file, so it lists the six forever
        // and no migration can or should clear it. `000870`'s docblock says so
        // itself: "the six DO end up constrained. `000870`'s `REFUSED` is a filter
        // on `000870`'s OWN loop, not a schema property". What changed is the
        // SCHEMA, and this test now measures the schema rather than the constant.
        self::assertNotSame(
            [],
            $refused,
            'REFUSED is empty, so this proof is vacuous. The historical record of the six relations 000870 '
            .'declined lives in that constant, and it is what gives the empty width-incompatible set below '
            .'its meaning. An empty list means the migration stopped filtering, so "nothing is incompatible" '
            .'would describe a broken discovery rather than a healthy family.',
        );

        // DERIVED ONCE AND REUSED. `familyRelations()` re-derives the family from
        // the executed schema on every call, and this method used to call it five
        // times: once for the leg loop and once inside the per-child loop below.
        // On real MySQL that cost MEASURED 5m53s for this single test.
        //
        // It also returns ONE ROW PER COLUMN, not one per relation, which is why
        // the compatible list below holds 238 entries for 7 relations. Both facts
        // are stated here because the count assertions on it depend on them.
        $familyRelations = $this->familyRelations($db);

        $incompatible = [];
        $compatible = [];

        foreach ($familyRelations as $relation) {
            $mismatch = null;
            foreach ($relation['columns'] as $leg) {
                $child = $this->columnType($db, $relation['child'], $leg);
                $parent = $this->columnType($db, $relation['parent'], $leg);

                self::assertNotNull($child, "{$relation['child']}.{$leg} does not exist on this engine");
                self::assertNotNull($parent, "{$relation['parent']}.{$leg} does not exist on this engine");

                if (strtolower((string) $child) !== strtolower((string) $parent)) {
                    $mismatch = sprintf(
                        '%s.%s is %s but %s.%s is %s',
                        $relation['child'], $leg, $child,
                        $relation['parent'], $leg, $parent,
                    );

                    break;
                }
            }

            if ($mismatch === null) {
                $compatible[] = $relation['child'];

                continue;
            }
            $incompatible[$relation['child']] = $mismatch;
        }

        // THE ASSERTION THAT INVERTED. The original required the refused set and
        // the width-incompatible set to be EQUAL. They no longer are, and that
        // disagreement IS the success: MEASURED on MySQL 8.4.11 this test failed
        // with "Width-incompatible: ." — an EMPTY incompatible set. The six
        // relations are no longer width-incompatible because
        // `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php` widened
        // `tf_dialog_filters.id` to bigint.
        self::assertSame(
            [],
            array_keys($incompatible),
            'these relations are still width-INCOMPATIBLE on this engine, so MySQL would refuse a constraint '
            .'over them: '.implode(' | ', $incompatible).'. Every one of them is UNCONSTRAINED and will break '
            .'the first real install. A widened parent with unwidened children is precisely the hazard this '
            .'family must keep out of, so this must be empty.',
        );

        // AND THE SIX ARE ALL LANDED — the claim the two sets being unequal used to
        // make impossible to state. This replaces "the refused set EQUALS the
        // incompatible set" with the statement that actually matters now: the
        // historical refusals are CONSTRAINED, so nothing is silently skipped.
        $unconstrained = [];
        foreach (array_keys($refused) as $child) {
            $parent = null;
            foreach ($familyRelations as $relation) {
                if ($relation['child'] === $child) {
                    $parent = $relation['parent'];

                    break;
                }
            }
            if ($parent === null) {
                continue;
            }

            $found = false;
            foreach ($db->getSchemaBuilder()->getForeignKeys($child) as $foreign) {
                if ((string) ($foreign['foreign_table'] ?? '') === $parent) {
                    $found = true;

                    break;
                }
            }
            if (! $found) {
                $unconstrained[] = $child.' → '.$parent;
            }
        }

        self::assertSame(
            [],
            $unconstrained,
            'relations the historical REFUSED list named carry NO constraint on this engine: '
            .implode('; ', $unconstrained).'. The widening migration and the six constraints were supposed to '
            .'land together — if the widening ran and the constraints did not, the schema is in the one state '
            .'that is worse than either: a bigint parent whose children are unconstrained.',
        );

        self::assertCount(
            6,
            $refused,
            'the refusal list is expected to name exactly the six depth-1 relations. A different count means the '
            .'list moved, and both this test and the widening migration need re-reading before the schema is '
            .'trusted. Observed: '.implode(', ', array_keys($refused)),
        );

        // THE CONTROL HALF of the re-baseline, and the reason the empty
        // incompatible set above is evidence rather than a discovery failure.
        self::assertNotSame(
            [],
            $compatible,
            'no relation in this family is width-compatible, so the loop above examined nothing and its '
            .'empty result is vacuous rather than healthy.',
        );

        // RE-BASELINED. The original required the ONLY compatible relation to be
        // the grandchild, because it was the only one `000870` constrained. Now
        // that `001100` has widened the root, EVERY relation in the family is
        // compatible, and asserting a singleton here would fail on the very
        // success this re-baseline exists to record.
        //
        // So the claim is inverted to the total: all seven discovered relations are
        // width-compatible. The count is asserted rather than a transcribed list,
        // because the discovery above is what produced them.
        $uniqueCompatible = array_values(array_unique($compatible));
        self::assertCount(
            7,
            $uniqueCompatible,
            'expected every one of this family\'s 7 relations to be width-compatible now that '
            .self::FILTER.'.id is bigint, but only '.count($uniqueCompatible).' distinct relations are. A '
            .'relation still carrying the old width is one MySQL would refuse a constraint over. Compatible: '
            .implode(', ', $uniqueCompatible),
        );

    }

    /**
     * The live proof that the depth-1 refusal is NECESSARY: MySQL refuses the leg.
     *
     * The constraint is ATTEMPTED against the real engine and required to fail.
     * Without this, `REFUSED` would be an assertion in a docblock that nothing on
     * the target engine ever checked — which is how the six relations could have
     * shipped as a migration-time 3780 on the first real install.
     *
     * CONTROL HALF, and it is the part that makes the refusal evidence: the
     * depth-2 shape — `bigint` child onto a `bigint` parent, same signedness,
     * same leg set, same ON DELETE CASCADE — is ATTEMPTED the same way and is
     * required to be ACCEPTED. A server refusing every FK in this suite would
     * fail here, so the 3780 above is attributable to the column width and not
     * to the harness.
     */
    public function test_the_refused_depth_1_leg_is_refused_by_mysql_3780(): void
    {
        $db = $this->migratedMysqlConnection();

        // ---- CONTROL HALF: the depth-2 shape is ACCEPTED. UNCHANGED. ----
        $db->statement(
            'ALTER TABLE `'.self::GRANDCHILD.'` ADD CONSTRAINT `fk_f7_control_depth2_shape` '
            .'FOREIGN KEY (`account_id`,`id`) REFERENCES `'.self::TITLE.'` (`account_id`,`id`) '
            .'ON DELETE CASCADE',
        );
        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::GRANDCHILD, self::TITLE, 'fk_f7_control_depth2_shape'),
            'the control ALTER was accepted but did not create the cascade it declared. This engine is not '
            .'behaving as the rest of this suite assumes, so the depth-1 assertion below would prove nothing.',
        );
        $db->statement('ALTER TABLE `'.self::GRANDCHILD.'` DROP FOREIGN KEY `fk_f7_control_depth2_shape`');

        // ---- THE REAL HALF: RE-BASELINED — the depth-1 shape is now ACCEPTED. ----
        //
        // This assertion is INVERTED, not deleted. It used to require the ALTER to
        // be REFUSED with driver code 3780, and its own failure message said what
        // would make it stale:
        //
        //     "Either tf_dialog_filters.id has been re-keyed to bigint — in which
        //      case all six refused relations become landable and the migration,
        //      its SQLite gate and this lane are all stale"
        //
        // That is precisely what happened:
        // `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php` widened the
        // column, and MEASURED on MySQL 8.4.11 this test then failed with
        // "MySQL ACCEPTED the depth-1 leg tf_dialog_filters_color → tf_dialog_filters
        // that this migration refuses to write."
        //
        // A depth-1 leg that does NOT bite is the dangerous state, not the safe
        // one, so the direction flips: the leg must now be ACCEPTED and must now
        // carry the CASCADE it declares. Keeping the control half above is what
        // keeps this evidence — a server refusing every FK in the suite fails
        // there, so an acceptance here is attributable to the column width.
        $db->statement(
            'ALTER TABLE `'.self::REFUSED_WITNESS.'` ADD CONSTRAINT `fk_f7_depth1_shape_now_accepted` '
            .'FOREIGN KEY (`account_id`,`id`) REFERENCES `'.self::FILTER.'` (`account_id`,`id`) '
            .'ON DELETE CASCADE',
        );

        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::REFUSED_WITNESS, self::FILTER, 'fk_f7_depth1_shape_now_accepted'),
            'the depth-1 leg was ACCEPTED but did not create the cascade it declared. '.self::FILTER.'.id is '
            .'bigint and '.self::REFUSED_WITNESS.'.id is bigint, so a bigint → bigint leg must carry its rule — '
            .'a missing one means the ALTER was not the constraint this test thinks it is.',
        );

        // And it really BITES, which the old version never had to check because a
        // refused leg bites nothing. Same operation as the old control half, now
        // load-bearing: with the constraint present, the orphan must be refused by
        // the engine rather than landing in the table forever.
        $orphanRefused = null;
        try {
            $db->table(self::REFUSED_WITNESS)->insert([
                'account_id' => self::TENANT_ACCOUNT_ID,
                'id' => self::ABSENT_FILTER,
                'color' => 77,
            ]);
        } catch (QueryException $e) {
            $orphanRefused = $e;
        }

        self::assertNotNull(
            $orphanRefused,
            'the depth-1 constraint exists and its cascade was created, yet an orphan row naming a filter that '
            .'does not exist LANDED. Either the constraint is not enforced on this session, or it was created '
            .'over a different column set than ('.self::FILTER.'.account_id, id) → ('.self::REFUSED_WITNESS
            .'.account_id, id).',
        );
        self::assertSame(
            1452,
            (int) ($orphanRefused->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row) from the depth-1 leg; got: '
            .$orphanRefused->getMessage().' / errorInfo='.json_encode($orphanRefused->errorInfo),
        );

        $db->statement('ALTER TABLE `'.self::REFUSED_WITNESS.'` DROP FOREIGN KEY `fk_f7_depth1_shape_now_accepted`');

        // The leg the DIAL installs must be present too — this is the assertion
        // the old version made at the end, and it is now the load-bearing one.
        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::REFUSED_WITNESS, self::FILTER),
            self::REFUSED_WITNESS.' carries NO constraint to '.self::FILTER.' in the finished schema. The widen '
            .'migration and its six constraints were supposed to land together; if the widening ran and the '
            .'constraints did not, the schema is in the one state worse than either: a bigint parent whose '
            .'children are unconstrained.',
        );
    }

    /**
     * DEPTH 1 — the orphan LANDS, because its constraint is refused and absent.
     *
     * The control half of the depth-2 tests, read as the honest depth-1
     * assertion: asserting an orphan refusal here would assert a constraint the
     * target engine has already refused to accept. What has to hold is that the
     * row is admitted, with a real tenant, on a connection whose
     * `foreign_key_checks` was read back as 1 by `MySqlTestCase`.
     */
    public function test_depth_1_an_orphan_is_refused_because_the_depth_1_leg_now_exists(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);

        // RE-BASELINED 2026-10-05. The direction is INVERTED and the row is now
        // expected to be REFUSED, because the depth-1 leg now exists.
        //
        // This test's own failure message said what would invalidate it:
        //
        //     "If it does not land, tf_dialog_filters.id has been re-keyed and
        //      every refusal in this family is stale."
        //
        // It did land once — with a 1452 —
        //
        //     Integrity constraint violation: 1452 … `tf_dialog_filters_color`,
        //     CONSTRAINT `tf_dialog_filters_color_df_widen_fk`
        //     FOREIGN KEY (`account_id`,`id`) REFERENCES `tf_dialog_filters`
        //
        // — which is the signal the message predicted. `001100` widened the parent
        // and landed the six constraints, so this leg now bites.
        //
        // The assertion is kept in its new direction because a depth-1 leg that
        // does NOT refuse its own parent is the dangerous state, not the safe one:
        // the row would sit in the table forever, invisible.
        //
        // Renamed so the name stops asserting the opposite of what the test proves.
        $row = $this->childRow(self::ABSENT_FILTER);
        $this->assertRowCoversRequiredColumns($db, self::REFUSED_WITNESS, $row);

        $refused = null;
        try {
            $db->table(self::REFUSED_WITNESS)->insert($row);
        } catch (QueryException $e) {
            $refused = $e;
        }

        self::assertNotNull(
            $refused,
            'a '.self::REFUSED_WITNESS.' row naming a filter that does not exist LANDED. The leg to '
            .self::FILTER.' now exists and cascades, so this row must be refused — if it is not, the widening '
            .'landed without its constraints and the schema holds unconstrained children under a re-keyed '
            .'parent, which is strictly worse than either uniform state.',
        );
        self::assertSame(
            1452,
            (int) ($refused->errorInfo[1] ?? 0),
            'the depth-1 orphan must be refused with MySQL 1452 (cannot add or update a child row); got: '
            .$refused->getMessage().' / errorInfo='.json_encode($refused->errorInfo),
        );
        self::assertSame(
            0,
            $db->table(self::REFUSED_WITNESS)->where('id', self::ABSENT_FILTER)->count(),
            'the refused row must not be present in any form.',
        );

        // ---- CONTROL HALF: unchanged, and still load-bearing. The tenant FK is
        // enforced on this very connection, so the depth-1 refusal just proved
        // above is evidence about the leg under test rather than about a
        // permissive session. Same operation, a DIFFERENT constraint, deliberately.
        $thrown = null;
        try {
            $db->table(self::REFUSED_WITNESS)->insert([
                'account_id' => self::FABRICATED_ACCOUNT_ID,
                'id' => self::ABSENT_FILTER,
                'color' => 43,
            ]);
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'a row naming a tenant that does not exist was ACCEPTED on a migrated MySQL connection. Enforcement '
            .'is not on, so the depth-1 refusal asserted above proves nothing.',
        );
        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'the fabricated tenant must be refused with 1452; got: '.$thrown->getMessage(),
        );
    }

    // ------------------------------------------------------------------
    // DEPTH 2 — grandchild → tf_dialog_filters_title
    // ------------------------------------------------------------------

    public function test_depth_2_a_valid_grandchild_row_lands_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);
        $this->seedFilter($db, self::PLANTED_FILTER);
        $this->seedTitle($db, self::PLANTED_FILTER);

        $row = $this->grandchildRow(self::PLANTED_FILTER);
        $this->assertRowCoversRequiredColumns($db, self::GRANDCHILD, $row);

        $db->table(self::GRANDCHILD)->insert($row);

        $landed = $db->table(self::GRANDCHILD)
            ->where('account_id', self::TENANT_ACCOUNT_ID)
            ->where('id', self::PLANTED_FILTER)
            ->first();

        self::assertNotNull(
            $landed,
            'the legitimate depth-2 row must LAND once its parent '.self::TITLE.' exists. A depth-2 leg that '
            .'refuses its own parent\'s children is an outage, not a safety net.',
        );
        foreach (['account_id', 'id', 'position'] as $column) {
            self::assertSame(
                $row[$column],
                (int) $landed->{$column},
                "the column {$column} must come back as the value inserted.",
            );
        }
    }

    /**
     * The depth-2 orphan, refused with MySQL's own 1452 — with its ROOT planted.
     *
     * The root filter EXISTS here and only the middle hop is missing, so neither
     * the tenant constraint nor the depth-1 constraint can be what refuses this
     * row. That is what makes the second depth independently provable rather than
     * a repeat of the first.
     */
    public function test_depth_2_an_orphan_is_refused_with_mysql_error_1452(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);
        $this->seedFilter($db, self::ABSENT_FILTER);

        // ---- CONTROL HALF: enforcement OFF, the orphan MUST land. ----
        $landed = false;
        $db->statement('SET SESSION foreign_key_checks = 0');
        try {
            $db->table(self::GRANDCHILD)->insert($this->grandchildRow(self::ABSENT_FILTER));
            $landed = $db->table(self::GRANDCHILD)->where('id', self::ABSENT_FILTER)->exists();
        } finally {
            $db->statement('SET SESSION foreign_key_checks = 1');
        }

        self::assertTrue(
            $landed,
            'with foreign_key_checks = 0 the depth-2 orphan MUST land. Its tenant AND its root filter are both '
            .'real, so the ONLY constraint that could refuse it is the depth-2 leg to '.self::TITLE
            .' — which is exactly what the 1452 assertion below must be measuring.',
        );

        $db->table(self::GRANDCHILD)->where('id', self::ABSENT_FILTER)->delete();

        // ---- THE REAL HALF: enforcement ON, the same insert MUST raise. ----
        $thrown = null;
        try {
            $db->table(self::GRANDCHILD)->insert($this->grandchildRow(self::ABSENT_FILTER));
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull(
            $thrown,
            'the depth-2 orphan was ACCEPTED with foreign_key_checks = 1. Its tenant and its ROOT filter are '
            .'both valid, so neither the tenant constraint nor the depth-1 constraint can be what should have '
            .'refused it — the leg to '.self::TITLE.' is absent, and the second depth is not proven on this '
            .'engine.',
        );

        self::assertSame(
            1452,
            (int) ($thrown->errorInfo[1] ?? 0),
            'expected MySQL error 1452 (cannot add or update a child row); got: '
            .$thrown->getMessage().' / errorInfo='.json_encode($thrown->errorInfo),
        );

        self::assertSame(
            0,
            $db->table(self::GRANDCHILD)->where('id', self::ABSENT_FILTER)->count(),
            'the refused depth-2 orphan left a row behind.',
        );
    }

    // ------------------------------------------------------------------
    // THE CHAIN CASCADES ON THE ENGINE THAT HONOURS IT
    // ------------------------------------------------------------------

    /**
     * ON DELETE CASCADE is proven AT THE HOP THAT ACTUALLY EXISTS, and the hop
     * that does not exist is asserted as a gap rather than assumed.
     *
     * The delete rule is READ BACK from `information_schema` rather than assumed
     * from the migration text, so a later migration that retargets or drops the
     * leg fails HERE with the actual rule in the message.
     *
     * ## WHY THE CASCADE IS MEASURED FROM THE MIDDLE HOP
     *
     * "Delete the root and the grandchild goes too" is FALSE here, because the
     * depth-1 leg is refused — there is no cascade from `tf_dialog_filters` to
     * `tf_dialog_filters_title` for a cascade to travel along. The SQLite lane
     * asserts the same shape, and both lanes record it, because a reader who
     * assumed a root-anchored cascade would conclude that deleting a filter
     * prunes its entities. On this schema it does not.
     */
    public function test_the_hop_two_cascade_is_proven_and_the_hop_one_gap_is_recorded(): void
    {
        $db = $this->migratedMysqlConnection();

        $this->seedTenant($db, self::TENANT_ACCOUNT_ID);
        $this->seedFilter($db, self::PLANTED_FILTER);
        $this->seedTitle($db, self::PLANTED_FILTER);
        $db->table(self::GRANDCHILD)->insert($this->grandchildRow(self::PLANTED_FILTER));

        self::assertSame(
            1,
            $db->table(self::GRANDCHILD)->count(),
            'the seeded depth-2 row is not there, so the cascade below would prove nothing.',
        );

        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::GRANDCHILD, self::TITLE),
            self::GRANDCHILD.' \u{2192} '.self::TITLE.' is not ON DELETE CASCADE on this engine, so the '
            .'depth-2 leg would not reach its children.',
        );
        self::assertSame(
            'CASCADE',
            $this->deleteRule($db, self::TITLE, self::FILTER),
            self::TITLE.' carries no constraint to '.self::FILTER.' on this engine. The depth-1 leg was widened '
            .'and landed by 2026_10_04_001100, so it must now CASCADE — a missing rule here means the hop-one '
            .'cascade proved below is being measured against an incomplete schema.',
        );

        // ---- HOP 2: the cascade this family actually has ----
        $deleted = $db->table(self::TITLE)
            ->where('account_id', self::TENANT_ACCOUNT_ID)
            ->where('id', self::PLANTED_FILTER)
            ->delete();

        self::assertSame(1, $deleted, 'the middle-hop row itself was not deleted');
        self::assertSame(
            0,
            $db->table(self::GRANDCHILD)->count(),
            'deleting '.self::TITLE.' must take its entities with it (ON DELETE CASCADE), which is the depth-2 '
            .'leg doing its job on the engine that will actually run it.',
        );

        // ---- HOP 1: RE-BASELINED. The cascade this family NOW HAS. ----
        //
        // This block used to assert the OPPOSITE and said why:
        //
        //     "deleting tf_dialog_filters took tf_dialog_filters_title with it -
        //      which it does NOT do, because the depth-1 leg is refused and
        //      therefore absent."
        //
        // and the `assertNull` above it existed only to prove that hop was absent.
        // Both were invalidated by `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php`,
        // which widened `tf_dialog_filters.id` and landed the six depth-1 constraints.
        // MEASURED on MySQL 8.4.11 this test failed with:
        //
        //     Failed asserting that 'CASCADE' is null.
        //
        // So hop one is now asserted as the cascade it is, which is also the only
        // direction that says anything useful: a depth-1 leg that does NOT cascade
        // leaves entities orphaned by a filter deletion, which is the failure the
        // operator named - a surprise of relational data.
        $this->seedTitle($db, self::PLANTED_FILTER);
        $db->table(self::GRANDCHILD)->insert($this->grandchildRow(self::PLANTED_FILTER));
        self::assertSame(
            1,
            $db->table(self::GRANDCHILD)->count(),
            'the depth-2 fixture did not land, so the hop-1 assertion below would prove nothing.',
        );

        $db->table(self::FILTER)
            ->where('account_id', self::TENANT_ACCOUNT_ID)
            ->where('id', self::PLANTED_FILTER)
            ->delete();

        self::assertSame(
            0,
            $db->table(self::TITLE)->count(),
            'deleting '.self::FILTER.' must take '.self::TITLE.' with it (ON DELETE CASCADE over the depth-1 '
            .'leg). If the title survives, the hop-one cascade does not exist and every entity row under a '
            .'deleted filter is an orphan.',
        );
        self::assertSame(
            0,
            $db->table(self::GRANDCHILD)->count(),
            'the grandchild must be gone too: deleting '.self::FILTER.' reaches '.self::TITLE.' over hop one '
            .'and the grandchild over hop two, so the whole subtree is pruned. A surviving entity row would '
            .'mean one of the two hops does not cascade.',
        );
    }

    // ------------------------------------------------------------------
    // fixtures
    // ------------------------------------------------------------------

    /**
     * The tenant row, exactly as the executed `telegram_accounts` requires it.
     *
     * Every NOT NULL column without a default is supplied, and the payload is
     * checked against the engine's own `information_schema` first.
     */
    private function seedTenant(Connection $db, int $userId): void
    {
        $row = [
            'user_id' => $userId,
            'label' => 'mysql-track-'.$userId,
            'type' => 'user',
            'dc_id' => 2,
        ];

        $this->assertRowCoversRequiredColumns($db, 'telegram_accounts', $row);
        $db->table('telegram_accounts')->insert($row);
    }

    /**
     * The root filter row, checked against the engine before it is inserted.
     *
     * `$filterId` is the parameter so the SAME builder produces the planted row
     * and the coordinates the depth-1 orphan names — which is what makes the two
     * halves of the refusal test the SAME insert.
     */
    private function seedFilter(Connection $db, int $filterId): void
    {
        $row = [
            'account_id' => self::TENANT_ACCOUNT_ID,
            'id' => $filterId,
            'constructor' => self::FILTER,
        ];

        $this->assertRowCoversRequiredColumns($db, self::FILTER, $row);
        $db->table(self::FILTER)->insert($row);
    }

    /** The middle hop — the depth-2 relations' parent. */
    private function seedTitle(Connection $db, int $filterId): void
    {
        $row = [
            'account_id' => self::TENANT_ACCOUNT_ID,
            'id' => $filterId,
            'text' => 'mysql track title',
        ];

        $this->assertRowCoversRequiredColumns($db, self::TITLE, $row);
        $db->table(self::TITLE)->insert($row);
    }

    /** The depth-1 orphan: a REAL tenant, and a filter that does not exist. */
    private function childRow(int $filterId): array
    {
        return [
            'account_id' => self::TENANT_ACCOUNT_ID,
            'id' => $filterId,
            'color' => 42,
        ];
    }

    /** The depth-2 orphan: a REAL tenant, and a title row that does not exist. */
    private function grandchildRow(int $titleId): array
    {
        return [
            'account_id' => self::TENANT_ACCOUNT_ID,
            'id' => $titleId,
            'position' => 0,
            'constructor' => 'textEntity',
        ];
    }

    /**
     * The delete rule MySQL reports for the constraint from `$child` to `$parent`,
     * or null when there is no such constraint.
     */
    private function deleteRule(Connection $db, string $child, string $parent, ?string $constraint = null): ?string
    {
        $row = $db->selectOne(
            'SELECT rc.DELETE_RULE AS rule_name
               FROM information_schema.REFERENTIAL_CONSTRAINTS rc
               JOIN information_schema.KEY_COLUMN_USAGE kcu
                 ON kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
                AND kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
              WHERE rc.CONSTRAINT_SCHEMA = DATABASE()
                AND rc.TABLE_NAME = ?
                AND rc.REFERENCED_TABLE_NAME = ?
                AND (? IS NULL OR rc.CONSTRAINT_NAME = ?)
              LIMIT 1',
            [$child, $parent, $constraint, $constraint],
        );

        return $row === null ? null : (string) $row->rule_name;
    }

    /**
     * Assert the payload is legal against the EXECUTED table: every column it
     * names exists, and it supplies every NOT NULL column the engine has no
     * default for.
     *
     * Read off the engine rather than typed from the migration, so this is a
     * statement about the schema that will actually receive the insert.
     */
    private function assertRowCoversRequiredColumns(Connection $db, string $table, array $row): void
    {
        $columns = $db->select(
            'SELECT COLUMN_NAME AS name, IS_NULLABLE AS nullable, COLUMN_DEFAULT AS def, EXTRA AS extra
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$table],
        );

        self::assertNotEmpty($columns, "no columns found for {$table} — the dial did not create it");

        $present = [];
        $required = [];

        foreach ($columns as $column) {
            $name = (string) $column->name;
            $present[] = $name;

            $autoIncrement = stripos((string) $column->extra, 'auto_increment') !== false;
            $hasDefault = $column->def !== null;

            if (strtoupper((string) $column->nullable) === 'NO' && ! $hasDefault && ! $autoIncrement) {
                $required[] = $name;
            }
        }

        foreach (array_keys($row) as $name) {
            self::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — the fixture is written against a "
                .'schema the shipped dial does not produce.',
            );
        }

        $missing = array_values(array_diff($required, array_keys($row)));

        self::assertSame(
            [],
            $missing,
            "the {$table} insert omits NOT NULL columns with no default: ".implode(', ', $missing),
        );
    }
}
