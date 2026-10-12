<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;

/**
 * A sqlite connection whose FOREIGN KEY constraints are actually enforced —
 * or which refuses to hand one out.
 *
 * ## WHY THIS CLASS EXISTS AT ALL
 *
 * SQLite ships with `PRAGMA foreign_keys` OFF, and so does every connection
 * `ShippedStoreSchema::capsuleFor()` builds — MEASURED, not assumed:
 * a fresh capsule reports `PRAGMA foreign_keys` = `{"foreign_keys":0}`, the
 * same value `ShippedStoreSchema`'s own docblock states.
 *
 * With that pragma off, a constraint declared in the shipped DDL is INERT:
 * the DDL text is present in `sqlite_master` and `Schema::getForeignKeys()`
 * still reports it, so every assertion that only *reads* the schema passes —
 * while every assertion that *behaves* as if the constraint existed also
 * passes, because nothing raises. A negative test written as
 * `$this->expectException(QueryException::class); <orphan insert>` is green
 * under the pragma-off default for the wrong reason, and would stay green if
 * the constraint were deleted outright.
 *
 * ## THE "THREE DEFECTS" CLAIM IS INHERITED, AND THIS FILE DOES NOT VOUCH FOR IT
 *
 * The plans repeat that three real defects shipped green through this gap. It
 * is quoted here as the warning it is, and NOT as a result, because the tree
 * does not support the causation:
 *
 *  - The three plans that repeat it word it three different ways — "during
 *    this branch"
 *    (`docs/superpowers/plans/2026-10-04-foreign-key-structure-plan.md:105-106`),
 *    "during the previous plan"
 *    (`docs/superpowers/plans/2026-10-04-foreign-key-structure.md:22`) and
 *    "during the NF5 plan"
 *    (`docs/superpowers/plans/2026-10-04-foreign-key-structure.md:122-123`,
 *    originating at
 *    `docs/superpowers/plans/2026-10-02-nf5-mirror-correctness.md:55-57`).
 *  - `git log` puts those three fixes at the TIP of this branch — 35ed3d90,
 *    737d095d, 7c8498c5 — so "the previous branch" would be wrong whatever it
 *    meant.
 *  - And the mechanism does not fit. Those three are the keying tasks of the
 *    plan this one is BLOCKED BY
 *    (`docs/superpowers/plans/2026-10-04-mirror-writer-integrity.md:85,249,339`)
 *    — a peer-blind primary key, a malformed row discarding its page, a
 *    fabricated media key. FK enforcement rejects a row whose PARENT IS
 *    ABSENT; it says nothing about a row whose own key is wrong, and none of
 *    those three is an orphan.
 *
 * What IS measured, and is the whole reason this class exists: a negative
 * assertion written under the pragma-off default is vacuous. That is proven
 * by execution in
 * `RelationConstraintsAreEnforcedTest`, and it is claimed nowhere here beyond
 * that measurement.
 *
 * ## THE CONTRACT, AND WHY IT IS AN ASSERTION RATHER THAN A STATEMENT
 *
 * `enforcingConnection()` turns the pragma on and then READS IT BACK, and
 * fails the test if the read-back is not 1. It never returns a connection
 * whose enforcement state it has not observed.
 *
 * The read-back is not ceremony. SQLite treats `PRAGMA foreign_keys` as a
 * no-op inside a transaction, so a suite that wraps its fixture in
 * `DB::beginTransaction()` (or in a `RefreshDatabase`/`DatabaseTransactions`
 * trait) gets the pragma silently ignored — the statement returns no error,
 * the enforcement never happens, and every negative assertion underneath
 * becomes green for the wrong reason.
 *
 * MEASURED, not quoted from the SQLite docs: turning the pragma on inside an
 * open transaction on this exact connection read back `0`, and the same
 * statement issued after `rollBack()` read back `1`. So the assertion below
 * is the difference between a negative test and a decoration.
 *
 * ## HOW A CALLER PROVES THE HARNESS BITES
 *
 * Turning a gate on is not the same as knowing it can fail.
 * `RelationConstraintsAreEnforcedTest::test_the_pragma_decides_whether_an_orphan_row_is_rejected()`
 * runs the SAME orphan insert twice — once on a plainly-built connection and
 * once through this method — and requires the first to land and the second to
 * raise. A harness that was unconditionally permissive would raise in both
 * passes, and that test's control half is what would catch it.
 */
abstract class RelationConstraintTestCase extends TestCase
{
    private mixed $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        // `ShippedStoreSchema::capsuleFor()` installs its own container and
        // facade application globally. Tests here extend the plain PHPUnit
        // TestCase rather than Testbench, but the process is shared, so a
        // leaked facade application would carry this suite's sqlite state into
        // whatever ran next.
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        parent::tearDown();
    }

    /**
     * Boot the named shipped migrations and return a connection on which
     * foreign keys are ENFORCED — asserted, not assumed.
     *
     * The pragma is set AFTER `capsuleFor()` has run every `up()`, so the
     * connection is in whatever state migration DDL leaves it in when the
     * pragma is issued — and the read-back below reports that state honestly
     * rather than a state the helper arranged. That is the whole point of the
     * assertion: it is what makes a future dialect, or a fixture that opens a
     * transaction, fail here instead of silently going permissive.
     *
     * @param  string  ...$migrations  absolute paths to shipped migration files, in apply order
     */
    final protected function enforcingConnection(string ...$migrations): Connection
    {
        $db = ShippedStoreSchema::capsuleFor(...$migrations)->getConnection();

        $before = $this->foreignKeysPragma($db);
        Assert::assertSame(
            0,
            $before,
            'ShippedStoreSchema boots sqlite with PRAGMA foreign_keys OFF, and that is the whole '
            .'reason this harness exists. If this ever reads 1, the default changed upstream and the '
            .'assertion below is no longer proving anything — re-read '
            .'docs/superpowers/plans/2026-10-04-foreign-key-structure-plan.md §3 before touching it.',
        );

        $db->statement('PRAGMA foreign_keys = ON');

        Assert::assertSame(
            1,
            $this->foreignKeysPragma($db),
            'PRAGMA foreign_keys = ON did not take effect on this connection. SQLite treats the pragma '
            .'as a NO-OP inside a transaction, so a fixture wrapped in beginTransaction() (or a '
            .'RefreshDatabase/DatabaseTransactions trait) turns it off without error. A connection '
            .'handed back with enforcement unverified is the vacuous-negative-test condition the plans '
            .'warn about; see the class docblock for why that warning is inherited, not measured.',
        );

        return $db;
    }

    /**
     * The CURRENT value of `PRAGMA foreign_keys`, read back from the engine.
     *
     * `Connection::scalar()` (not `selectOne()`) because the pragma's result
     * column is driver-named `foreign_keys` on sqlite but the accessor should
     * not have to care: this reads the FIRST column of the first row, whatever
     * the driver calls it.
     */
    protected function foreignKeysPragma(Connection $db): int
    {
        $value = $db->scalar('PRAGMA foreign_keys');

        Assert::assertNotNull(
            $value,
            'PRAGMA foreign_keys returned no row at all. That is not "off" and not "on" — it means the '
            .'driver is not sqlite, so enforcingConnection() is being pointed at a dialect whose pragma '
            .'semantics this method has NOT verified, and its assertions would be theatre.',
        );

        return (int) $value;
    }
}
