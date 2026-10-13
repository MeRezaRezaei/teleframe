<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\MySql;

use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;

/**
 * THE THREE CURATED `bytes` COLUMNS MUST HOLD ANY TL `bytes` VALUE THE WIRE CARRIES.
 *
 * ## THE INVARIANT, STATED AS A PROPERTY OF THE COLUMNS AND NOT OF ANY MIGRATION FILE
 *
 * Three curated migrations declare `string('bytes', 2048)`:
 *
 *   src/Laravel/Migrations/2026_09_14_200020_create_tf_documents_tables.php:68
 *     → tf_documents_thumbs.bytes
 *   src/Laravel/Migrations/2026_09_14_200021_create_tf_photos_tables.php:36
 *     → tf_photos_sizes.bytes
 *   src/Laravel/Migrations/2026_09_14_200023_create_tf_sticker_sets_tables.php:56
 *     → tf_sticker_sets_thumbs.bytes
 *
 * A TL `bytes` value is stored as LOWERCASE HEX, from `bin2hex()` at the one
 * encode site `MirrorFactDecomposer::encodeBytes()`
 * (`src/Schema/Mirror/MirrorFactDecomposer.php:717`). Hex is exactly two ASCII
 * characters per raw byte, so `VARCHAR(2048)` is a HARD CEILING OF 1024 RAW
 * BYTES on the value Telegram can actually deliver. A `photoSize` or a
 * `documentThumb` above 1 KiB does not raise anything the operator sees: MySQL
 * raises 1406 / 1265, and BOTH are members of
 * `MirrorFactWriter::VALUE_NOT_STORABLE_MYSQL_CODES`
 * (`src/Schema/Mirror/MirrorFactWriter.php:351`), so the row is SILENTLY SKIPPED
 * and the mirror quietly disagrees with Telegram. That is the defect.
 *
 * ## ⛔ WHY THIS FILE CANNOT BE PROVEN ON SQLITE, AND WHY IT SAYS SO ITSELF
 *
 * SQLite DOES NOT ENFORCE `VARCHAR(n)` LENGTH AT ALL. This is not a claim from
 * memory — it is recorded as measured, twice, in this repository:
 *
 *   `MirrorFactWriter::VALUE_NOT_STORABLE_SQLSTATES`'s docblock
 *     (src/Schema/Mirror/MirrorFactWriter.php): "measured on sqlite 3.46.1, a
 *      40-char and a 400-char value both landed in a `varchar(8)` column
 *      without raising"
 *
 *   `tests/Mirror/MirrorBytesColumnEncodingTest.php`'s class docblock:
 *     "sqlite is lenient where MySQL is not … A test that waits for a
 *      QueryException would therefore pass on sqlite forever while production
 *      still dies."
 *
 * So a bytes-overflow test run against the default sqlite track is VACUOUSLY
 * GREEN BY CONSTRUCTION: it can never fail, on any engine state, for the reason
 * it exists. The proof requires the MySQL track (`TELEFRAME_MYSQL=1`, see
 * `tests/Concerns/RunsMysqlMigrations.php`), which is why this class extends
 * `MySqlTestCase` — the base class whose own docblock exists to stop a test
 * asserting enforcement on a connection whose migration was silently skipped —
 * and why the base class SKIPS the whole file when the track is off rather than
 * running it in a state where it means nothing.
 *
 * ## WHAT EACH TEST ASSERTS, IN EACH OF THE TWO ENGINE STATES — stated, not skipped
 *
 * There are exactly two shapes these three columns can be in on a real install,
 * and this file is a RED-BEFORE / GREEN-AFTER discriminator between them. It is
 * NOT tolerant in the sense of skipping:
 *
 *   STATE A — column bounded (`varchar`, a small CHARACTER_MAXIMUM_LENGTH).
 *     BOTH test methods FAIL. Proof 1 reports the VERBATIM MySQL error for a
 *     4000-raw-byte insert, driver code included, plus the ceiling the engine
 *     actually imposes expressed in RAW BYTES (CHARACTER_MAXIMUM_LENGTH / 2,
 *     because hex is 2 chars per byte). Proof 2 reports the declared shape. A
 *     silent skip here would be a vacuous green, which is precisely the failure
 *     mode this repository's MySQL track was built to eliminate — a track that
 *     silently skips is a track nobody reads.
 *
 *   STATE B — column unbounded (`text`/`mediumtext`/`longtext`).
 *     Proof 1 asserts the WHOLE round trip: insert 4000 raw bytes of varied
 *     binary, re-select, and require `strlen($readBack) === strlen($hex)` AND
 *     `$readBack === $hex`. Proof 2 asserts the declaration. Both PASS.
 *
 * Silent truncation is caught in either state without relying on strict mode,
 * because the assertion is exact string equality and a truncated value is never
 * equal to the original at a different length.
 *
 * ## WHY THE VALUE IS VARIED BINARY AND NOT `str_repeat('ab', n)`
 *
 * A 4000-byte value built from ONE repeated pair is the classic way a
 * bytes-overflow test goes blind: hex truncation lands on an even character
 * boundary, an even boundary lands on a whole number of `'ab'` pairs, and the
 * truncated value is byte-identical to the original for its whole length. That
 * fixture cannot distinguish "stored intact" from "stored truncated" whenever the
 * engine happens to cut at an even offset — which is most of the time.
 *
 * This value cycles ALL 256 byte values (stride 37, coprime with 256, so the
 * cycle period is exactly 256 and every value in the range appears), and its
 * length 4000 is not a multiple of 256 (4000 % 256 = 160), so no byte position
 * aliases another and no head/tail coincidence can make a cut invisible. The
 * value also deliberately contains 0x00 and 0xFF, which is the shape of a real
 * `photoSize` and is exactly what a naive fixture of printable characters would
 * never carry.
 *
 * ## WHY THE FIXTURE ROWS ARE BUILT FROM `information_schema` AND NOT TYPED
 *
 * Every insert here reads the EXECUTED table's columns, nullability, defaults
 * and auto-increment flags off the engine first, then supplies a legal value for
 * every NOT NULL column that has no default, and asserts that the payload both
 * names only real columns and omits none of the required ones. A transcribed
 * column list is a second copy of the dial that drifts silently: if a column is
 * added, the old list produces an insert MySQL refuses with 1054, and if one is
 * dropped, it produces an insert MySQL accepts while writing nothing the test
 * means. Reading the engine makes both impossible.
 *
 * The tenant row is planted too, because `2026_10_04_000700` gives every `tf_*`
 * table an `account_id` constraint onto `telegram_accounts.user_id` — without a
 * real tenant the inserts would be refused with 1452 and the failure would name
 * the wrong defect.
 *
 * ## WHY `bytes` IS ALWAYS SUPPLIED EXPLICITLY
 *
 * `bytes` currently ships with `->default(DB::raw("('')"))`. Under a TEXT
 * retype, MySQL forbids a literal default on a TEXT column outright (error
 * 1101), so whether the column keeps a default depends entirely on how the
 * widening migration is written — and both outcomes are legal. The insert
 * therefore always passes `bytes` itself, which is correct under either
 * declaration and keeps this test measuring the column's CAPACITY rather than
 * its DEFAULT.
 *
 * ## CLEANUP
 *
 * Every row this file inserts is deleted in a `finally`, children before parents
 * and the tenant last, because the MySQL track's connection is process-memoised
 * and a leftover row would be visible to the next test class in this lane.
 */
final class TfBytesColumnsAreUnboundedOnMysqlTest extends MySqlTestCase
{
    /** A tenant id no plausible real id collides with; this file plants it. */
    private const TENANT = 908172635;

    /** The parent-fact id this file plants in each of the three parent tables. */
    private const ENTITY_ID = 908172635;

    /**
     * RAW BYTES in the value under test — four times the 1024-raw-byte ceiling
     * that `varchar(2048)` imposes on a hex-encoded bytes column.
     *
     * Not a multiple of 256 (4000 % 256 = 160), so the tail cannot alias the
     * head. See the class docblock.
     */
    private const RAW_BYTES = 4000;

    /**
     * The three curated `bytes` columns, each with the parent fact its foreign
     * key `['account_id', 'id']` points at.
     *
     * @var array<string, string> child table => parent table
     */
    private const TARGETS = [
        'tf_documents_thumbs' => 'tf_documents',
        'tf_photos_sizes' => 'tf_photos',
        'tf_sticker_sets_thumbs' => 'tf_sticker_sets',
    ];

    /** Column under test. */
    private const COLUMN = 'bytes';

    /** @var list<string> tables this file inserted into, in insert order */
    private array $inserted = [];

    protected function tearDown(): void
    {
        $this->purgeInsertedRows();

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // PROOF 1 — the round trip: > 1024 raw bytes in, the same hex out
    // ------------------------------------------------------------------

    /**
     * A `bytes` value of MORE THAN 1024 RAW BYTES persists INTACT into each of
     * the three `bytes` columns and reads back byte-identical.
     *
     * Every table is attempted even after one fails, so a single run reports all
     * three rather than only the first — the three columns are declared in three
     * separate migrations and there is no reason to believe they agree.
     */
    public function test_a_bytes_column_holds_more_than_1024_raw_bytes_intact_on_mysql(): void
    {
        $db = $this->migratedMysqlConnection();

        $hex = self::oversizedHex();
        $raw = hex2bin($hex);

        // The anti-vacuity guard, asserted BEFORE any insert: if this value ever
        // stopped being longer than the ceiling it claims to clear, the whole
        // test would silently degrade into a check that a short string round
        // trips. 4000 raw bytes = 8000 hex characters, against a ceiling of
        // 2048 characters.
        self::assertSame(self::RAW_BYTES, strlen($raw), 'the fixture must decode to exactly RAW_BYTES raw bytes');
        self::assertGreaterThan(
            1024,
            strlen($raw),
            'the fixture must be LARGER than the 1024-raw-byte ceiling that varchar(2048) imposes on hex, '
            .'or this test proves nothing about overflow',
        );
        self::assertSame(strlen($raw) * 2, strlen($hex), 'hex must be exactly two characters per raw byte');

        $problems = [];
        $proven = 0;

        // 🔴 SEEDED ONCE, 2026-10-10, and this was a real failure of this file's own
        // making rather than a stale expectation. MEASURED:
        //
        //     Illuminate\Database\UniqueConstraintViolationException: SQLSTATE[23000]:
        //     Integrity constraint violation: 1062 Duplicate entry '908172635' for key
        //     'telegram_accounts.telegram_accounts_user_id_unique'
        //
        // `seedTenant()` was called INSIDE the loop below, once per entry in
        // self::TARGETS (three of them), and every call inserts the SAME row —
        // `user_id` = self::TENANT, the one tenant all three parent tables hang off.
        // `telegram_accounts.user_id` is UNIQUE, so the second insert violates it and
        // the run died on iteration two of three, before it ever reached the overflow
        // assertion this file exists to make.
        //
        // The tenant is per-TEST, not per-target: it is the parent of the parent. One
        // seed, before the loop. `$this->inserted[]` then records it once, which is
        // also what the teardown wants — it deletes by name, and the row was created
        // once.
        $this->seedTenant($db);

        foreach (self::TARGETS as $child => $parent) {

            $parentRow = $this->minimalRow($db, $parent, [
                'account_id' => self::TENANT,
                'id' => self::ENTITY_ID,
            ]);
            $db->table($parent)->insert($parentRow);
            $this->inserted[] = $parent;

            $childRow = $this->minimalRow($db, $child, [
                'account_id' => self::TENANT,
                'id' => self::ENTITY_ID,
                'position' => 0,
                self::COLUMN => $hex,
            ]);

            // Read back what the engine says this column can hold, BEFORE the
            // insert, so the failure message below can name the ceiling that
            // rejected the value rather than only the error.
            $declared = $this->declaredCapacity($db, $child);

            try {
                $db->table($child)->insert($childRow);
            } catch (QueryException $e) {
                $problems[] = sprintf(
                    "%s: the INSERT of %d raw bytes (%d hex chars) was REFUSED.\n"
                    ."  MySQL verbatim : %s\n"
                    ."  driver code    : %s\n"
                    ."  declared column: %s\n"
                    ."  implied ceiling: %s raw bytes (CHARACTER_MAXIMUM_LENGTH %s / 2, because bytes are stored hex)\n"
                    ."  both 1406 (ER_DATA_TOO_LONG) and 1265 (ER_WARN_DATA_OUT_OF_RANGE) are members of\n"
                    ."  MirrorFactWriter::VALUE_NOT_STORABLE_MYSQL_CODES, so in the live mirror this row is\n"
                    ."  SILENTLY SKIPPED — no exception reaches the operator and Telegram's value is lost.",
                    $child,
                    self::RAW_BYTES,
                    strlen($hex),
                    $e->getMessage(),
                    json_encode($e->errorInfo ?? null),
                    $declared['summary'],
                    $declared['rawCeiling'],
                    $declared['maxLength'] === null ? 'n/a' : $declared['maxLength'],
                );

                continue;
            }

            $this->inserted[] = $child;

            $readBack = $db->table($child)
                ->where('account_id', self::TENANT)
                ->where('id', self::ENTITY_ID)
                ->where('position', 0)
                ->value(self::COLUMN);

            if (! is_string($readBack)) {
                $problems[] = "{$child}: the row did not land — the insert reported success but nothing reads back";

                continue;
            }

            if (strlen($readBack) !== strlen($hex)) {
                $problems[] = sprintf(
                    '%s: the value was TRUNCATED on the way in. Read back %d hex characters (%d raw bytes); '
                    .'inserted %d hex characters (%d raw bytes). A silently shortened bytes column corrupts the '
                    .'mirror with no error anywhere — exact-length equality is what catches it.',
                    $child,
                    strlen($readBack),
                    intdiv(strlen($readBack), 2),
                    strlen($hex),
                    self::RAW_BYTES,
                );

                continue;
            }

            if ($readBack !== $hex) {
                $problems[] = sprintf(
                    '%s: the value came back the RIGHT LENGTH (%d hex chars) but not the right bytes — the '
                    .'column altered the content rather than storing it, at first difference offset %d.',
                    $child,
                    strlen($hex),
                    self::firstDifference($hex, $readBack),
                );

                continue;
            }

            $proven++;
        }

        self::assertSame(
            [],
            $problems,
            implode("\n\n", $problems),
        );

        self::assertSame(
            count(self::TARGETS),
            $proven,
            'not every bytes column round-tripped its oversized value, so this run does not prove the '
            .'invariant for the whole family',
        );
    }

    // ------------------------------------------------------------------
    // PROOF 2 — the declaration: no character ceiling below the TEXT floor
    // ------------------------------------------------------------------

    /**
     * Each `bytes` column is declared with a character ceiling of at least 65535,
     * which is the smallest of MySQL's TEXT family and the floor for "unbounded".
     *
     * This is the independent half of the argument, and it is deliberately NOT a
     * re-reading of proof 1's outcome: proof 1 measures what the column DOES with
     * an oversized value, this one measures what the engine SAYS the column is.
     * They fail together for one cause and would diverge the moment somebody
     * raised the ceiling to, say, `varchar(8192)` — which is enough for most
     * thumbnails and still fails for the values Telegram actually delivers.
     */
    public function test_every_bytes_column_is_declared_without_a_small_character_ceiling(): void
    {
        $db = $this->migratedMysqlConnection();

        $problems = [];
        $checked = 0;

        foreach (self::TARGETS as $child => $parent) {
            $declared = $this->declaredCapacity($db, $child);
            $checked++;

            $ceiling = $declared['maxLength'];

            if ($ceiling !== null && (int) $ceiling < 65535) {
                $problems[] = sprintf(
                    '%s.%s is declared %s — a ceiling of %s CHARACTERS, which is %s RAW BYTES of a '
                    .'hex-encoded TL bytes value. Unbounded is the requirement, and 65535 is MySQL\'s own '
                    .'floor for it (the smallest of text/mediumtext/longtext).',
                    $child,
                    self::COLUMN,
                    $declared['summary'],
                    $ceiling,
                    $declared['rawCeiling'],
                );
            }
        }

        self::assertGreaterThan(
            0,
            $checked,
            'no bytes column was inspected, so this proof is vacuous — the dial did not create the family',
        );

        self::assertSame([], $problems, implode("\n", $problems));
    }

    // ------------------------------------------------------------------
    // helpers
    // ------------------------------------------------------------------

    /**
     * 4000 raw bytes covering EVERY value 0x00-0xFF, at a stride coprime with
     * 256 so the cycle period is exactly 256 and no two positions 256 apart in
     * range carry the same byte.
     *
     * Length 4000 is not a multiple of 256, so the value's tail cannot be a copy
     * of its head — a truncation at any offset changes the bytes that follow it.
     * That is what makes the exact-equality assertion in proof 1 a real test
     * rather than a coincidence.
     */
    private static function oversizedHex(): string
    {
        $raw = '';

        for ($i = 0; $i < self::RAW_BYTES; $i++) {
            $raw .= chr(($i * 37 + 11) % 256);
        }

        return bin2hex($raw);
    }

    /** Offset of the first byte where two strings differ, or their common length. */
    private static function firstDifference(string $a, string $b): int
    {
        $limit = min(strlen($a), strlen($b));

        for ($i = 0; $i < $limit; $i++) {
            if ($a[$i] !== $b[$i]) {
                return $i;
            }
        }

        return $limit;
    }

    /**
     * What the ENGINE says this column is, plus the raw-byte ceiling that implies.
     *
     * Read from `information_schema.COLUMNS`, never transcribed from a migration:
     * the whole point is to describe the schema that will actually receive the
     * insert, including one a widening migration has since changed.
     *
     * @return array{summary:string, dataType:string, maxLength:int|null, rawCeiling:string}
     */
    private function declaredCapacity(Connection $db, string $table): array
    {
        $row = $db->selectOne(
            'SELECT DATA_TYPE AS data_type, CHARACTER_MAXIMUM_LENGTH AS max_len,
                    IS_NULLABLE AS nullable, COLUMN_DEFAULT AS def
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$table, self::COLUMN],
        );

        self::assertNotNull(
            $row,
            "{$table} has no column named '".self::COLUMN."' on this engine — the shipped dial does not "
            .'produce the schema this test is written against, so every assertion here is vacuous.',
        );

        $dataType = (string) $row->data_type;
        $maxLength = $row->max_len === null ? null : (int) $row->max_len;

        return [
            'summary' => sprintf(
                '%s (nullable=%s, default=%s)',
                $dataType,
                (string) $row->nullable,
                $row->def === null ? 'NULL' : var_export($row->def, true),
            ),
            'dataType' => $dataType,
            'maxLength' => $maxLength,
            'rawCeiling' => $maxLength === null
                ? 'unbounded'
                : (string) intdiv($maxLength, 2),
        ];
    }

    /**
     * A `telegram_accounts` row: the tenant every `tf_*` table's `account_id`
     * constraint points at since `2026_10_04_000700`.
     */
    private function seedTenant(Connection $db): void
    {
        $row = $this->minimalRow($db, 'telegram_accounts', [
            'user_id' => self::TENANT,
            'label' => 'tf-bytes-'.self::TENANT,
            'type' => 'user',
        ]);

        $db->table('telegram_accounts')->insert($row);
        $this->inserted[] = 'telegram_accounts';
    }

    /**
     * A minimal row that the EXECUTED table accepts.
     *
     * Built from `information_schema`: every column the engine declares NOT NULL
     * with no default and no AUTO_INCREMENT gets a legal value for its type,
     * unless `$overrides` supplies one. Then the result is checked against the
     * engine in both directions — it names only real columns, and omits none of
     * the required ones — so a fixture written against a schema this dial does
     * not produce fails HERE with a column name, instead of failing later at the
     * engine with an error about something else.
     *
     * @param  array<string, int|string>  $overrides
     * @return array<string, int|string>
     */
    private function minimalRow(Connection $db, string $table, array $overrides): array
    {
        $columns = $db->select(
            'SELECT COLUMN_NAME AS name, DATA_TYPE AS data_type, IS_NULLABLE AS nullable,
                    COLUMN_DEFAULT AS def, EXTRA AS extra
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
              ORDER BY ORDINAL_POSITION',
            [$table],
        );

        self::assertNotEmpty($columns, "no columns found for {$table} — the dial did not create it");

        $present = [];
        $required = [];
        $row = [];
        $ordinal = 0;

        foreach ($columns as $column) {
            $name = (string) $column->name;
            $present[] = $name;

            if (array_key_exists($name, $overrides)) {
                continue;
            }

            $autoIncrement = stripos((string) $column->extra, 'auto_increment') !== false;
            $hasDefault = $column->def !== null;

            if (strtoupper((string) $column->nullable) === 'YES' || $hasDefault || $autoIncrement) {
                continue;
            }

            $required[] = $name;
            $row[$name] = $this->valueForType((string) $column->data_type, $ordinal++);
        }

        foreach (array_keys($overrides) as $name) {
            self::assertContains(
                $name,
                $present,
                "{$table} has no column '{$name}' on this engine — this fixture is written against a schema "
                .'the shipped dial does not produce.',
            );
        }

        // Every NOT NULL column with no default must have received a value.
        //
        // The previous assertion here was `array_diff($required, array_keys($overrides)) === []`,
        // which can never hold for any parent carrying more than two such columns:
        // the loop `continue`s an overridden column at the top, so an override NEVER reaches
        // `$required`, which makes the diff identical to `$required` itself. The check therefore
        // degenerated into "this table has no NOT NULL columns", which every parent table fails —
        // it asserted a fact about the FIXTURE that was never true, not about the migration.
        // What the fixture actually guarantees is coverage, and that is what is asserted now.
        foreach ($required as $name) {
            self::assertArrayHasKey(
                $name,
                $row,
                "the {$table} fixture leaves NOT NULL column '{$name}' (no default) without a value",
            );
        }

        return array_merge($row, $overrides);
    }

    /**
     * A value the engine will accept for `$dataType`, or a loud failure naming the
     * type — because guessing wrong here would produce a fixture that reads as a
     * proof of something it never wrote.
     */
    private function valueForType(string $dataType, int $ordinal): int|string
    {
        return match ($dataType) {
            'tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint', 'year', 'bit' => 0,
            'decimal', 'numeric', 'float', 'double', 'real' => 0,
            'date' => '2020-01-01',
            'time' => '00:00:00',
            'datetime', 'timestamp' => '2020-01-01 00:00:00',
            'char', 'varchar', 'binary', 'varbinary',
            'tinytext', 'text', 'mediumtext', 'longtext',
            'tinyblob', 'blob', 'mediumblob', 'longblob' => 'probe-'.$ordinal,
            default => self::fail(
                "no legal fixture value is known for data type '{$dataType}'. Extend valueForType() rather "
                .'than letting the engine refuse the row — an unexplained 1264/1366 here would be '
                .'indistinguishable from the defect this file exists to prove.'
            ),
        };
    }

    /**
     * Remove every row this file planted, children before parents.
     *
     * The MySQL track's connection is process-memoised, so a row left behind
     * here is visible to the next test class in this lane. Children go first so
     * no delete ever trips a foreign key.
     */
    private function purgeInsertedRows(): void
    {
        if ($this->inserted === []) {
            return;
        }

        try {
            $db = $this->migratedMysqlConnection();

            foreach (array_reverse($this->inserted) as $table) {
                $query = $db->table($table);

                if ($table === 'telegram_accounts') {
                    $query->where('user_id', self::TENANT);
                } elseif (in_array($table, self::TARGETS, true)) {
                    $query->where('account_id', self::TENANT)
                        ->where('id', self::ENTITY_ID)
                        ->where('position', 0);
                } else {
                    $query->where('account_id', self::TENANT)
                        ->where('id', self::ENTITY_ID);
                }

                $query->delete();
            }
        } catch (\Throwable) {
            // Cleanup is best effort: a failure here must not mask the real
            // assertion, and `prepareMysqlSchemaForTest()` resets every row in
            // the database between tests anyway.
        } finally {
            $this->inserted = [];
        }
    }
}
