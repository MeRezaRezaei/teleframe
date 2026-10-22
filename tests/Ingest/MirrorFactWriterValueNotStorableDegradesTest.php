<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * THE BLAST RADIUS OF ONE UNSTORABLE ROW: a value that does not fit its column
 * must cost that row ALONE, and take the rest of the ingest page down with it
 * never.
 *
 * ## THE DEFECT THIS FILE EXISTS FOR, as it was found live
 *
 * `MirrorFactWriter::write()` classified `QueryException` into per-row clues for
 * FOREIGN KEY, NOT NULL and unknown-column, and re-raised EVERYTHING else. Two
 * real MySQL errors fell into that re-raise, and each one killed a whole ingest
 * page:
 *
 *  - **1366** "Incorrect string value '\xCE\xC8...' for column 'stripped_thumb'"
 *    — raw TL `bytes` decoded as a text column's charset.
 *  - **1364** "Field 'constructor' doesn't have a default value" — and this one
 *    is the sharper lesson, because the 1364 message does **not** contain the
 *    string `NOT NULL`, so the existing substring arm could never have matched
 *    it. A `constructor` column with a bare defaultless declaration took the
 *    page's transaction with it.
 *
 * Both are PER-ROW facts, exactly like a row that cannot cover its live primary
 * key, and they were being treated as per-PAGE ones. The cost is amplified by
 * `MirrorIngesterFactory::buildResultRoute()`, which wraps the write in
 * `$db->transaction(...)`: one unstoreable row rolled back the other twenty, and
 * `inserted` — the one number a caller acts on, since
 * `MirrorUpdateIngester::ingest()` emits `UpdateStored` on `inserted > 0` — was
 * never even consulted for the rows that had landed.
 *
 * (Corrected 2026-10-04: this sentence said "which is what every caller advances
 * a watermark and emits an event on". No caller advances a watermark on
 * `inserted` — the pts watermark `teleframe:ingest:gate` holds is a decision
 * over the four clue buckets, and the bus path holds none — and exactly one
 * caller emits an event on it. The invariant the argument actually needs is
 * unchanged and weaker: a row the mirror does not hold must not be counted as
 * placed.)
 *
 * ## WHY THAT MAKES IT A PER-ROW FACT AND NOT A BUG REPORT
 *
 * A row whose value does not fit its column is a fact about the DECOMPOSITION
 * of that one row: a thumb that is raw bytes, a constructor field the shipped
 * DDL gives no default. It is not a fact about the connection, the disk, or the
 * transaction. Collapsing those two things is the whole defect, so the guard
 * stays TARGETED — four MySQL driver codes and one precise Postgres SQLSTATE —
 * and a genuinely unclassifiable failure (a deadlock, a lost connection, a full
 * disk) still raises, which
 * `test_an_unclassifiable_error_still_raises` pins.
 *
 * ## HOW A MYSQL CODE IS SIMULATED ON SQLITE — stated, not glossed over
 *
 * It cannot be produced by sqlite. These codes are MySQL DRIVER codes, they live
 * in `PDOException::$errorInfo[1]`, and sqlite does not even enforce `VARCHAR(n)`
 * length so it has no such failure to give; its own SQLSTATE is `HY000` for every
 * error it produces. `MysqlFaultInjectingConnection` therefore runs the REAL
 * shipped DDL, lets the REAL insert genuinely FAIL against it, and rewrites only
 * the driver code and message at the connection boundary — the exact layer where
 * a real MySQL driver supplies its own code in place of sqlite's. The writer, the
 * schema, the loop and the exception object are all production code. Nothing here
 * calls the classifier directly, because a test that did would prove nothing
 * about the loop, and the loop is the subject.
 *
 * The fixture is `tf_channel_participants`, whose `constructor` column ships as
 * `$table->string('constructor', 64)` with no default and outside the primary
 * key — so a row that covers the key and omits `constructor` reaches the insert
 * and is refused, which is the operator's own 1364 verbatim. Every test here
 * runs the shipped migrations through `ShippedStoreSchema::capsuleFor()` and
 * transcribes no DDL.
 */
final class MirrorFactWriterValueNotStorableDegradesTest extends TestCase
{
    /**
     * `tf_channel_participants` ships `primary(['account_id','channel_id','user_id'])`
     * while `constructor` is a `string(64)` with NO default, outside that key.
     *
     * So a row carrying the whole key but omitting `constructor` is ADDRESSABLE
     * (it passes `placeRow()`'s key coverage check) and still cannot be stored —
     * which is the one shape that reaches the `catch` arm this file is about.
     * src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php:76
     */
    private const PARTICIPANTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php';

    /**
     * `tf_messages_reply_markup_rows` ships `primary(['account_id','id','position'])`
     * and carries no foreign key, so it is the honest SURVIVOR: a row it fully
     * satisfies, used to prove that the page around the bad row still lands.
     */
    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    private const ACCOUNT = 7;

    /**
     * The four MySQL DRIVER codes in this class, each with the message a real
     * server sends. Asserted as DATA rather than as a single example on purpose:
     * 1265/1366/1406 are the "value does not fit" family and 1364 is the
     * "no default to fall back on" case, and a guard that covers only the first
     * would still have taken the operator's page down.
     *
     * @return array<string, array{int, string}>
     */
    public static function mysqlUnstoreableFaults(): array
    {
        return [
            '1366 incorrect string value / encoding' => [
                1366,
                "Incorrect string value '\xCE\xC8\xCF\xD2\xC0' for column 'stripped_thumb' at row 1",
            ],
            '1265 data truncated for column' => [
                1265,
                "Data truncated for column 'stripped_thumb' at row 1",
            ],
            '1406 data too long for column' => [
                1406,
                "Data too long for column 'stripped_thumb' at row 1",
            ],
            '1364 field has no default value' => [
                1364,
                "Field 'constructor' doesn't have a default value",
            ],
        ];
    }

    /**
     * Errors that are NOT about one row's value fitting one column, and must
     * therefore still escape `write()` and take the transaction down.
     *
     * This is the SAFETY half of the change. A guard that swallows everything
     * would pass every other test in this file while being strictly worse than
     * the bug it replaced: a lost connection or a full disk reported as "one row
     * skipped" is a mirror that silently stops ingesting and says it is fine.
     *
     * @return array<string, array{int, string, string}>
     */
    public static function unclassifiableFaults(): array
    {
        return [
            '1213 deadlock' => [
                1213,
                'Deadlock found when trying to get lock; try restarting transaction',
                'HY000',
            ],
            '1045 access denied' => [
                1045,
                "Access denied for user 'teleframe'@'localhost'",
                '28000',
            ],
            '1040 too many connections' => [
                1040,
                'Too many connections',
                '08004',
            ],
        ];
    }

    /**
     * RED, THEN GREEN — the property under test.
     *
     * `inserted` counts STATEMENTS PLACED and nothing else, so an unstoreable
     * row must not move it. And the survivor must be in the TABLE, not merely
     * counted: the whole cost of this bug was that `$db->transaction(...)` in
     * `MirrorIngesterFactory::buildResultRoute()` rolled the page back, so
     * "the call returned" proves nothing unless the surviving row actually
     * persisted.
     *
     * (Corrected 2026-10-04: this said "counts rows PLACED", the false claim
     * `tests/Schema/Mirror/InsertedCountsStatementsNotRowsTest.php` now gates.
     * On THIS branch the two coincide — the fault arm raises out of
     * `placeRow()`, so `$inserted++` never runs — which is exactly why the
     * assertion below has to be read together with the ROW COUNT beside it, and
     * not as a general contract.)
     */
    #[DataProvider('mysqlUnstoreableFaults')]
    public function test_an_unstoreable_row_degrades_and_the_rest_of_the_page_still_lands(int $code, string $driverMessage): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection($code, $driverMessage);

            // The offending row: it COVERS the live primary key (so it is not
            // declined by placeRow()'s key-coverage arm) and omits `constructor`
            // (so the real insert is refused). Asserted as a precondition —
            // a fixture that quietly changed shape would make every assertion
            // below vacuous.
            $offending = ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66];
            self::assertArrayNotHasKey(
                'constructor',
                $offending,
                'the offending row must omit constructor, or nothing refuses it',
            );

            // FIXTURE CORRECTION (2026-10-03, Task 2.2): this survivor is a message
            // CHILD, so it carries the enclosing message's peer — `tf_messages` and
            // its children are keyed (account_id, peer_type, peer_id, id[,
            // position]) because Telegram numbers messages PER CHAT. A row that
            // could not cover every key column would be reported as an
            // UNPLACEABLE-KEY clue instead of reaching the VALUE-fault path these
            // tests are about, masking the thing under test. Every assertion is
            // unchanged.
            $survivor = [
                'account_id' => 1,
                'peer_type' => 1,
                'peer_id' => 77,
                'id' => 5,
                'position' => 0,
                'constructor' => 'keyboardButtonRow',
            ];

            // THE ASSERTION: this call RETURNS. Before the change, MySQL 1366 /
            // 1265 / 1406 / 1364 matched no arm and re-raised straight out of the
            // loop, out of `write()`, and out of the wrapping transaction.
            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => $offending],
                ['table' => 'tf_messages_reply_markup_rows', 'row' => $survivor],
            ]);

            self::assertSame(1, $db->injectedFaults(), 'the insert must really have been attempted and really refused');

            self::assertSame(
                1,
                $write['inserted'],
                'inserted counts STATEMENTS PLACED — the unstoreable row reached neither the '
                .'upsert nor $inserted++, and the row-count assertion beside this one is what '
                .'proves the survivor is stored',
            );
            self::assertSame(
                1,
                $db->table('tf_messages_reply_markup_rows')->count(),
                'the row queued behind the unstoreable one must actually be stored — the transaction must commit',
            );
            self::assertSame(
                0,
                $db->table('tf_channel_participants')->count(),
                'nothing half-written may remain for the row that could not be stored',
            );

            // And the reason must be REPORTED, in a bucket that says what
            // happened rather than what did not.
            self::assertCount(
                1,
                $write['valueClues'],
                'an unstoreable row degrades to exactly one per-row clue, in its own bucket',
            );
            $clue = $write['valueClues'][0];
            self::assertStringContainsString('tf_channel_participants', $clue, 'the clue must name the row that failed');
            self::assertStringContainsString((string) $code, $clue, 'the clue must carry the driver code, so the next reader knows which fault it was');
            self::assertStringContainsString('value', strtolower($clue), 'the clue must name the fault as a value/column fault');
            self::assertStringContainsString(
                $driverMessage,
                $clue,
                'the driver message must travel with the clue verbatim — the next reader needs the column and the bytes',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * THE LEDGER MUST NOT LIE. The bucket exists precisely because saying
     * "foreign key constraint failed" about a binary-encoding fault costs the
     * next person reading it real time — it sends them to the wrong place.
     *
     * So: a value/column fault is its own bucket, and it must NOT have been
     * folded into either of the two existing ones.
     */
    public function test_the_value_fault_is_its_own_bucket_and_not_folded_into_fk_or_unmapped(): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection(1366, "Incorrect string value '\xCE\xC8' for column 'stripped_thumb' at row 1");

            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
            ]);

            self::assertSame([], $write['fkClues'], 'an encoding fault is not a foreign key failure — reporting it as one is the lie this bucket exists to prevent');
            self::assertSame([], $write['unmappedClues'], 'an encoding fault is not a modelling gap — the shipped DDL has the column');
            self::assertCount(1, $write['valueClues']);
            self::assertStringNotContainsString(
                'FOREIGN KEY constraint failed',
                $write['valueClues'][0],
                'the clue must not borrow another fault\'s wording',
            );
            self::assertStringNotContainsString(
                'NOT NULL constraint failed',
                $write['valueClues'][0],
                'the clue must not borrow another fault\'s wording',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * REGRESSION — the FOREIGN KEY arm is untouched.
     *
     * That arm is a message-substring match and stays one; what is pinned here
     * is that the new arm did not swallow it, and that an FK failure still lands
     * in `fkClues` and nowhere else. The end-to-end proof against a real driver
     * stays where it already was, in
     * `tests/Mirror/MediaMirrorTest::test_child_before_parent_document_is_an_fk_clue`.
     */
    public function test_a_foreign_key_failure_still_lands_in_fk_clues(): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection(
                1452,
                'FOREIGN KEY constraint failed (`tf_channel_participants`.`account_id`, CONSTRAINT `fk_…` FOREIGN KEY (`account_id`) REFERENCES `telegram_accounts` (`id`))',
            );

            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
            ]);

            self::assertCount(1, $write['fkClues'], 'the pre-existing FK arm must behave exactly as before');
            self::assertStringContainsString('FOREIGN KEY', $write['fkClues'][0]);
            self::assertSame([], $write['valueClues'], 'an FK failure must not leak into the new bucket');
            self::assertSame([], $write['unmappedClues']);
            self::assertSame(0, $write['inserted']);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * REGRESSION — the NOT NULL arm is untouched, and the new bucket does not
     * reach for it.
     *
     * Unlike the two injected cases, this one uses the UNMODIFIED sqlite
     * connection, so what the writer sees is a genuine, unedited sqlite
     * `NOT NULL constraint failed: …` — precisely what the arm matched before
     * and must still match. That also documents the real difference between the
     * two: sqlite NAMES the constraint, MySQL's 1364 does not mention `NOT NULL`
     * anywhere, which is why the 1364 case needed its own code-level arm.
     */
    public function test_a_not_null_failure_still_lands_in_fk_clues(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(
                self::PARTICIPANTS_MIGRATION,
                self::MESSAGES_MIGRATION,
            )->getConnection();

            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
            ]);

            self::assertSame(0, $write['inserted']);
            self::assertCount(1, $write['fkClues'], 'the pre-existing NOT NULL arm must behave exactly as before');
            self::assertStringContainsString('NOT NULL', $write['fkClues'][0]);
            self::assertSame([], $write['valueClues'], 'a NOT NULL violation on sqlite must not leak into the new bucket');
            self::assertSame([], $write['unmappedClues']);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * REGRESSION — the unknown-column arm is untouched, AND the new arm does not
     * overlap it. MySQL's ER_BAD_FIELD_ERROR is 1054 with SQLSTATE `42S22`, and
     * it is deliberately NOT in the new list: "unknown column" is a modelling
     * gap, "your value does not fit the column" is a per-row data fault, and the
     * two belong in different buckets.
     */
    public function test_an_unknown_column_failure_still_lands_in_unmapped_clues(): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection(
                1054,
                "Unknown column 'nope' in 'field list'",
                '42S22',
            );

            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
            ]);

            self::assertSame(0, $write['inserted']);
            self::assertCount(1, $write['unmappedClues'], 'the pre-existing unknown-column arm must behave exactly as before');
            self::assertSame([], $write['valueClues'], 'a modelling gap must not be reported as a value fault');
            self::assertSame([], $write['fkClues']);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * THE SAFETY PROPERTY. If this stops raising, the guard has become a blanket
     * swallow and is WORSE than the bug it replaced: a deadlocked, denied or
     * saturated connection would be reported to the operator as "one row skipped"
     * while the mirror quietly stops ingesting.
     *
     * Each case is an error about the CONNECTION or the ENGINE, not about one
     * row's value, so none of them may be classified as a per-row fact.
     */
    #[DataProvider('unclassifiableFaults')]
    public function test_an_unclassifiable_error_still_raises(int $code, string $driverMessage, string $sqlState): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection($code, $driverMessage, $sqlState);

            $raised = null;

            try {
                (new MirrorFactWriter)->write($db, [
                    ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
                ]);
            } catch (QueryException $e) {
                $raised = $e;
            }

            self::assertInstanceOf(
                QueryException::class,
                $raised,
                "driver code {$code} is not about one row's value — write() must re-raise it, not swallow it",
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * A generic sqlite constraint failure — HY000, driver code 19 — is the
     * control for the whole file.
     *
     * It is the shape sqlite produces for EVERYTHING, which is why
     * `isUnknownColumn()` has a sqlite-only phrase fallback and why this class
     * exists at all. If 19 were classified, the new arm would be a loose catch
     * rather than a targeted one, and every genuine constraint violation on a
     * sqlite host would stop being reported.
     */
    public function test_a_bare_sqlite_constraint_failure_still_raises(): void
    {
        $previous = Container::getInstance();

        try {
            $db = $this->connection(19, 'UNIQUE constraint failed: tf_channel_participants.account_id, tf_channel_participants.channel_id, tf_channel_participants.user_id');

            $raised = null;

            try {
                (new MirrorFactWriter)->write($db, [
                    ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'channel_id' => 55, 'user_id' => 66]],
                ]);
            } catch (QueryException $e) {
                $raised = $e;
            }

            self::assertInstanceOf(
                QueryException::class,
                $raised,
                "sqlite's generic code 19 carries no meaning, so it must never be classified as a value fault",
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The shipped-DDL capsule, wrapped so the offending table's inserts fail
     * with the given MySQL driver code while every other query runs untouched.
     *
     * The schema is the shipped DDL executed by `capsuleFor()`, never a
     * transcription — the same discipline every other ingest test in this suite
     * works to, because a re-typed DDL is a second copy that silently drifts.
     */
    private function connection(int $code, string $driverMessage, string $sqlState = 'HY000'): MysqlFaultInjectingConnection
    {
        $capsule = ShippedStoreSchema::capsuleFor(
            self::PARTICIPANTS_MIGRATION,
            self::MESSAGES_MIGRATION,
        );

        return new MysqlFaultInjectingConnection(
            $capsule->getConnection()->getPdo(),
            'tf_channel_participants',
            $code,
            $driverMessage,
            $sqlState,
        );
    }
}
