<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Schema\Mirror;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use MeRezaRezaei\Teleframe\Ingest\MessageSearchIndexer;

/**
 * Fact writer — inserts decomposed mirror rows into the relational DB,
 * catching FK constraint violations as ingest clues.
 *
 * Owner verbatim 2026-09-14: "what ever forign key that fails gives us a
 * clue to the wrong path of ingesting we have so it lets us gradually
 * making the real data happen and working".
 *
 * The writer does NOT swallow FK failures — it reports them as clue strings.
 * An ingest path that feeds a child before its parent triggers an FK clue;
 * re-ordering to parent-first fixes the path. This is the core of the
 * "gradually making the real data happen" loop.
 *
 * ## ROWS ARE REORDERED PARENT-FIRST, BECAUSE THE OTHERWISE-SILENT BRANCH
 * OF THIS CLASS IS A PERMANENT, PERMANENTLY-SILENT LOSS OF PAYLOAD.
 *
 * Everything below treats a dropped row as a tolerated degradation. That
 * tolerance is only safe while a dropped row is RARE and VISIBLE. Neither
 * held, and both failures compounded:
 *
 *  1. **A dropped row is not counted.** `$inserted++` sits INSIDE the try,
 *     above the catch, so a FK violation that the catch turns into a clue
 *     string never reaches it — correct, and unchanged here. But
 *  2. **nothing ordered the rows.** Verified, not assumed:
 *     `grep -c "usort\|ksort\|array_multisort"` over
 *     `MirrorFactWriter.php`, `UpdateIngestor.php` and
 *     `MirrorIngesterFactory.php` returns `0 0 0`. The decomposer emits a
 *     parent and its children as siblings, and NOTHING decides which lands
 *     first. On a batch shaped `[child, parent]` the child insert raises an
 *     FK violation, the catch files a clue and RETURNS, and the batch
 *     continues to the parent, which lands perfectly. Net effect: the
 *     parent is in the mirror, the child is not, `inserted` never counted
 *     it, no exception escaped `write()`, and the entry was then ACKED — so
 *     nothing ever asks for that row again.
 *
 * MEASURED 2026-10-04, because this paragraph used to say the caller "advances
 * a WATERMARK on `inserted`". NO caller does, on any path: the pts watermark
 * `teleframe:ingest:gate` holds is a decision over the four CLUE BUCKETS
 * (`IngestGateCommand`, the `$holds` condition), and the bus path holds no
 * watermark at all — `IngestConsumer` acks the entry it handled and re-reads
 * only an UN-ACKED tail. So `inserted` was never the thing that moved a cursor
 * here. What did make the loss permanent was the ack.
 *
 * That is the difference between a ledger entry and data loss. A ledger
 * entry is retried; a watermark that has advanced past a row is not, and
 * because the bus is at-least-once over a page boundary rather than a
 * row boundary, the row is simply never asked for again. The mirror and
 * Telegram now disagree permanently, and nothing anywhere reports it.
 *
 * And this feed's out-of-order arrival is NORMAL, in the repo's own words:
 * `src/Teleframe/Ingest/SafeDelete.php:40-41` — "A mirror is fed out of
 * order, so a child row may legitimately be written before its parent
 * exists; promoting 38 of those to enforced FKs converts a tolerated
 * out-of-order write into a hard raise on Postgres." That sentence
 * describes a HOST that had no FKs. This repo ships exactly three
 * cross-domain enforced FKs
 * (`2026_09_14_299999_create_tf_foreign_keys.php`), and they land on the
 * three highest-volume children there are: `tf_messages_media`,
 * `tf_messages_entities` and `tf_messages_reply_markup_rows_buttons`.
 * Measured against the real shipped DDL with `PRAGMA foreign_keys=ON`, a
 * media child, an entities child and a keyboard button each arriving
 * BEFORE their parent are each refused — and each refusal is invisible.
 *
 * So `write()` orders the batch parent-first (`parentFirst()`), and that
 * is the WHOLE fix. Note what it is NOT, because the tempting version of
 * this change is the destructive one:
 *
 *  - **No constraint was weakened, dropped or deferred.** Not one line of
 *    DDL changed; the graph below is READ, never edited. Dropping an FK
 *    would convert the failure from silent-and-rare back into loud-and-
 *    constant, which is the noise the three curated FKs deliberately are
 *    not. Ordering loses no integrity guarantee whatsoever — after it, the
 *    constraint is satisfied rather than avoided.
 *  - **`inserted` still counts exactly what it counted.** Reordering moves
 *    no row across the `$inserted++` and adds none. A row that genuinely
 *    cannot be placed — a cycle, or an FK to a parent that is in no batch
 *    at all — is still dropped, still uncounted, and still reported as a
 *    clue. Ordering fixes the ordering fault; it does not paper over a
 *    missing one.
 *  - **`placeRow()` and all three clue buckets are untouched.**
 *
 * The order is derived from the LIVE SCHEMA, read through Laravel's own
 * `Builder::getForeignKeys()`, for the reason `columnListing()` and
 * `primaryKeyColumns()` already give twice in this file: the database is
 * the only copy of the DDL that cannot drift. Measured, the alternative
 * sources all undercount or couple to something else —
 * `mirror-catalog.json`'s `children` is a TL-CONSTRUCTOR field map
 * (`"FK→MessageMedia"`, `"1:N child"`) and never names a table, and that
 * file is under active edit by another lane besides; the migration files
 * are the truth but ship FKs from shared `Blueprint` helpers
 * (`linkChild`, `participantLink`, `channelUpdateLink`, `differenceLink`,
 * `factKey`) whose `$table` argument is the only thing that identifies the
 * CHILD, so a grep for `->foreign(` sees 4 statements and the schema has
 * far more constraints than that. Reading the migrated schema sees every
 * one of them, on every driver, including any a host adds itself.
 *
 * `parentFirst()` is a Kahn sort with THREE properties this path cannot
 * trade away, all of them argued in its own docblock: it TERMINATES on a
 * cyclic graph (the one input where a naive topological sort spins
 * forever — an unbounded sort would be a worse bug than the loss it fixes),
 * it is STABLE so already-parent-first input is reproduced byte for byte,
 * and it is LINEAR, because a re-checked row is re-registered under exactly
 * one blocking parent and each parent table unblocks its waiters once.
 *
 * ## The line drawn here: a column the shipped DDL does not have is a
 * MODELLING GAP, not a data error — and it is resolved BEFORE the insert,
 * not by catching it afterwards.
 *
 * A TL constructor legitimately carries fields the curated DDL never
 * modelled. `starGiftUnique` carries `availability_issued:int`; the shipped
 * `tf_saved_star_gifts_gift` DDL has `availability_total` but no
 * `availability_issued`. That is normal for a mirror — the wire is ahead of
 * the curated dial — and it must never be the reason `ingest()` blows up.
 * It is a fact the schema does not model, so the field is DROPPED and
 * REPORTED as a clue, and the rest of the row (every other column, and the
 * parent row that precedes it) still lands.
 *
 * The filter asks the DATABASE which columns the table has
 * (`getColumnListing`) rather than pattern-matching driver error text. That
 * choice is deliberate: SQLSTATE cannot do this job on SQLite at all
 * (measured — see the arm below), and error text is a per-driver guess.
 * Asking the DDL is exact and identical on sqlite, MySQL and Postgres.
 *
 * ## The third degradation arm: a row that cannot cover its LIVE KEY is skipped.
 *
 * Not raised, and not stored either. `placeRow()` returns a clue instead of
 * placing, and `inserted` stays honest about a row the mirror does not hold.
 * The reasoning — and the measured `tf_dialogs` default(0) that made a bare
 * insert write a dialog keyed on a nonexistent peer — is in `placeRow()`.
 *
 * ## THE FOURTH DEGRADATION ARM: a value that does not FIT ITS COLUMN is skipped.
 *
 * Added after this class killed two live ingest pages on the operator's own
 * account. MySQL raised, per row, and `write()` re-raised both because neither
 * matched an arm:
 *
 *  - **1366** "Incorrect string value '\xCE\xC8...' for column 'stripped_thumb'"
 *    — a TL `bytes` field decoded as text and aimed at a TEXT column.
 *  - **1364** "Field 'constructor' doesn't have a default value" — and this one
 *    is the sharper lesson, because the 1364 message does NOT contain the string
 *    `NOT NULL`. The NOT NULL arm above matches on that substring, so on MySQL
 *    it could never have fired: a `constructor` column with a bare defaultless
 *    declaration was invisible to it.
 *
 * WHY THAT IS A PER-ROW FACT AND NOT A WHOLE-BATCH DEFECT, which is the whole
 * point: one row whose value does not fit its column is a fact about the
 * DECOMPOSITION of that one row. It says nothing about the connection, the disk
 * or the transaction. And because `MirrorIngesterFactory::buildResultRoute()`
 * wraps this write in `$db->transaction(...)`, one such row rolled back the
 * other twenty queued behind it — while `inserted`, the number the one
 * event-emitting caller (`MirrorUpdateIngester::ingest()`) gates its
 * `UpdateStored` emit on, was never even consulted for the rows that had
 * landed. A row the mirror does not hold must never be counted as
 * placed, and a row that cannot be stored must never cost its neighbours.
 *
 * WHY THE ARM IS TARGETED RATHER THAN "CATCH EVERYTHING": widening the catch
 * would hide genuine whole-batch defects — a deadlocked connection, a full
 * disk, an access-denied user, a serialization failure — as per-row noise, and a
 * mirror that silently stops ingesting while reporting "one row skipped" is
 * worse than the crash it replaced. The list below is four MySQL DRIVER codes
 * and one precise Postgres SQLSTATE, and nothing matches on message text. Every
 * other failure still re-raises.
 *
 * WHY IT GETS ITS OWN BUCKET and is not folded into `fkClues`: the ledger is
 * read to find what failed and WHY. Recording a binary-encoding fault as
 * "foreign key constraint failed" is a lie, and it is an expensive one — it
 * sends the next reader to the relationship graph instead of the column. So the
 * three buckets name three genuinely different axes:
 *   `fkClues`       a RELATIONSHIP the mirror does not have (FK / NOT NULL)
 *   `unmappedClues` the SHIPPED DDL does not MODEL this field at all
 *   `valueClues`    the VALUE does not fit the column the DDL gives it
 *
 * A FIFTH axis arrived later and did NOT get a fifth bucket: a row that cannot have an
 * identity of its own (the UNIQUE arm below) is filed in `fkClues`, beside `placeRow()`'s
 * own "row cannot cover its primary key" decline, which is the same fact said by the
 * walker rather than by the database.
 *
 * RE-MEASURED 2026-10-04, and the answer is that the wart is COSMETIC — so no code changed.
 * Three measurements, each re-runnable:
 *
 *  - IT IS UNREACHABLE ON THE SHIPPED DDL. `placeRow()` resolves a primary-key repeat with
 *    one atomic `upsert()`, so the duplicate this arm was written for converges instead of
 *    raising: the same fact delivered twice returns `inserted: 2` with an EMPTY `fkClues`.
 *    And there is no second identity to collide on — 92 tables across the three shipped
 *    mirror-fact migrations, introspected with `pragma index_list`, carry ZERO non-primary
 *    UNIQUE indexes. What is left is the `primaryKeyColumns() === null` fallback documented
 *    on `SQLITE_UNIQUE_PHRASES`. A name that is only wrong on a path the shipped schema
 *    cannot reach costs a reader nothing.
 *  - NOTHING A HUMAN READS IS BYTE-IDENTICAL TO A MISSING PARENT'S. Forcing a secondary
 *    UNIQUE index and rendering the real clue through each terminal surface puts
 *    "row cannot cover its own identity — the database already holds this key" plus the
 *    driver's own "Duplicate entry ... for key ..." in front of the reader at
 *    `IngestGateCommand` (the `FK clue:` warn loop), at `BackfillCommand`'s three `mirror
 *    FK clue:` loops, and at `UpdateIngestor` (which logs the clue with no label at all).
 *    The body discriminates; only the PREFIX is shared, and the body is what names the fault.
 *  - NOBODY FILTERS ON THE BUCKET. `tf_ingest_clues` is written by `recordReport()` and read
 *    by no controller, view, query or dashboard in the repository, so its `bucket` column
 *    has no consumer that could act on the name. The one count an operator sees,
 *    `IngestGateCommand`'s `['fk clues', count(...)]`, is a count in a table of counts, and
 *    the clue body is printed directly beneath it.
 *
 * WHAT WOULD MAKE IT NON-COSMETIC, so this is not re-opened a third time: a mirror fact
 * table gaining a non-PRIMARY unique index (the arm becomes reachable); OR any surface that
 * FILTERS or ALERTS on `bucket` instead of printing the clue body — that is the moment the
 * name starts routing a human to the wrong graph. Neither is true today.
 *
 * A FIFTH BUCKET IS NOT FREE, and the honest count is corrected here because the previous
 * version of this paragraph carried a claim that is FALSE: it said "nothing iterates the
 * keys". Four sites DO iterate `TfIngestClue::buckets()` — `MirrorIngesterFactory` (the
 * `mergeClueReports()` and `hasClues()` folds), `IngestGateCommand` (the gate verdict), and
 * `TfIngestClue::recordReport()` (the ledger write). So a fifth bucket would be persisted
 * and would gate WITHOUT those four being edited. The eight files that name every bucket
 * EXPLICITLY are still real, and they are still the cost.
 *
 * PROVENANCE: the two observed messages and the tables that produced them,
 * `tf_documents_thumbs.stripped_thumb` (1366) and a defaultless `constructor`
 * on one of the mirror's forty-nine tables (1364). The tests that pin all of
 * this are `tests/Ingest/MirrorFactWriterValueNotStorableDegradesTest.php`,
 * which runs the shipped DDL on sqlite and injects the MySQL driver code at the
 * connection boundary — the one layer where a real driver supplies its own code.
 *
 * ## Why a re-delivery is NOT a raise, and why a wrong value still is.
 *
 * The bus is at-least-once, and three SHIPPED mechanisms produce a second
 * delivery of a fact we already stored: a crash mid-batch replays the un-acked
 * tail, `XAUTOCLAIM` steals a merely-slow worker's entries after 60s (its own
 * docblock says "the work is done twice"), and `replayDeadLetter()` re-publishes
 * deliberately. So a UNIQUE violation on a re-delivery is not a bug report —
 * it is the bus working as designed. It is CONVERGED, by the one atomic `upsert()`
 * on the table's real primary key (`placeRow()`, since 2026-10-04), which is the
 * same choice the curated live path makes at `UpdateIngestor::ingestImpl`.
 *
 * Every OTHER violation still rethrows, and the distinction is not cosmetic. A
 * FK violation, a NOT NULL violation or a type violation means the DDL HAS the
 * column and the VALUE we produced is wrong — a real bug in the decomposition
 * or the ingest path. Swallowing it would retire the exact signal the owner's
 * loop runs on, and would let the mirror silently diverge from Telegram. The
 * narrow unknown-column arm does not
 * weaken that: it fires only on the driver codes that MEAN "no such column",
 * and it is a backstop for the (non-Illaravel) connection case, not the
 * primary path. The value/column arm above narrows it no further than its own
 * four driver codes, and
 * `MirrorFactWriterValueNotStorableDegradesTest::test_an_unclassifiable_error_still_raises`
 * is what keeps that promise honest.
 *
 * The one exception this paragraph used to state without qualification is the
 * UNIQUE arm: a duplicate that `upsert()` cannot reach — because the table's
 * identity could not be introspected at all, so `placeRow()` fell back to a bare
 * insert — degrades to one row instead of the page. It is measured per driver on
 * the constants below, it is reachable on the default engine, and it does not touch
 * the promise above: every non-unique failure still re-raises.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php
 * (the shipped DDL that lacks the column), src/Schema/schema/sources/api.tl
 * (`starGiftUnique#85f0a9cd … availability_issued:int`).
 */
final class MirrorFactWriter
{
    /** The one table this writer populates the search index for. */
    private const SEARCHABLE_TABLE = 'tf_messages';

    /**
     * Per-write() clue buffer for the search-index hook.
     *
     * @var list<string>
     */
    private array $unmappedClues = [];

    /**
     * Resolved search indexer, or false once proven unavailable.
     *
     * false is a distinct third state from null on purpose: "not yet looked"
     * (null) versus "looked, and the host has not migrated tf_message_search"
     * (false). Collapsing them would either re-probe the schema on every single
     * row or permanently disable indexing on a host that migrates mid-run.
     */
    private MessageSearchIndexer|false|null $searchIndex = null;

    /**
     * SQLSTATE codes that mean "the column you named does not exist", per driver.
     *
     * Measured, not guessed:
     *  - MySQL    42S22 (ER_BAD_FIELD_ERROR, 1054) "Unknown column 'x' in 'field list'"
     *  - Postgres 42703 (undefined_column, 7)      'column "x" … does not exist'
     *  - SQLite   HY000 (driver code 1) — its GENERIC state, NOT the state of every
     *             error it raises: measured on sqlite 3.46.1 an integrity violation
     *             (UNIQUE and NOT NULL both) reports `23000` / 19 instead, so HY000
     *             names the non-integrity family rather than this fault, and the
     *             driver's own phrase is what identifies the case
     *             ("has no column named x" on INSERT, "no such column: x" on SELECT).
     *
     * Measured with an in-memory capsule booted by
     * `tests/Store/ShippedStoreSchema::capsuleFor()` against a probe table, one
     * statement per class: missing table / unknown column / syntax fault →
     * `HY000`/1; UNIQUE violation → `23000`/19; NOT NULL (omitted and explicit
     * NULL, INTEGER and TEXT) → `23000`/19.
     *
     * @var list<string>
     */
    private const UNKNOWN_COLUMN_SQLSTATES = ['42S22', '42703'];

    /**
     * SQLite phrases for the same condition. SQLite is the one driver whose
     * SQLSTATE is uninformative, so this is text matching — and it is
     * confined to the exact driver that needs it, rather than being applied
     * to every driver's message as a blanket substring test.
     *
     * @var list<string>
     */
    private const SQLITE_UNKNOWN_COLUMN_PHRASES = ['has no column named', 'no such column'];

    /**
     * MySQL DRIVER codes (NOT SQLSTATEs — these live in `errorInfo[1]`) that
     * mean "the value we produced does not fit the column the DDL gives it".
     *
     * Measured against the operator's live account, both of these killed a whole
     * ingest page:
     *  - 1366 ER_DATA_TOO_LONG / "Incorrect string value '\xCE\xC8…' for column
     *    'stripped_thumb'" — a TL `bytes` field decoded as text.
     *  - 1364 ER_NO_DEFAULT_FOR_FIELD / "Field 'constructor' doesn't have a
     *    default value". This one is the reason the arm reads CODES and not
     *    text: 1364's message never mentions `NOT NULL`, so the substring arm
     *    above could not have seen it on any host.
     *
     * 1265 ER_WARN_DATA_OUT_OF_RANGE ("Data truncated for column") and 1406
     * ER_DATA_TOO_LONG ("Data too long for column") are the two siblings of the
     * same fact and are included with it; leaving either out would let the
     * identical defect under a slightly different strict-mode setting re-raise
     * and take the page down again.
     *
     * Deliberately NOT in this list, and the reason is the difference between a
     * per-row data fault and a modelling gap: 1054 ER_BAD_FIELD_ERROR
     * (unknown column) and
     * 1451/1452 (FK) are handled by the other arms, each in its own bucket. 1048
     * ER_BAD_NULL_ERROR is handled by isNotNullViolation() below, in fkClues.
     *
     * @var list<int>
     */
    private const VALUE_NOT_STORABLE_MYSQL_CODES = [1265, 1364, 1366, 1406];

    /**
     * The one SQLSTATE, on the one driver, that means the same thing precisely
     * enough to classify on text of state rather than of message.
     *
     * Postgres reports an over-length string as SQLSTATE `22001`
     * (`string_data_right_truncation`) with a driver code of `0` — so for this
     * driver the STATE is the signal and the code is not. `22001` names exactly
     * this condition and nothing else, which is why it is safe to match while a
     * phrase like "value too long" would not be.
     *
     * SQLite has no arm here and cannot have one: it does not enforce `VARCHAR(n)`
     * length at all, so there is no such failure to catch — measured on sqlite
     * 3.46.1, a 40-char and a 400-char value both landed in a `varchar(8)` column
     * without raising. Its SQLSTATE cannot key this fault either, and not because
     * it is one constant: measured on the same build, an integrity violation
     * reports `23000`/19 and every other fault the generic `HY000`/1, so neither
     * state names THIS fault. Same reason `isUnknownColumn()` needs a sqlite-only
     * phrase fallback above.
     *
     * @var list<string>
     */
    private const VALUE_NOT_STORABLE_SQLSTATES = ['22001'];

    /**
     * DRIVER CODES / STATES that mean "this row cannot have an identity of its own here".
     *
     * ## WHY THIS ARM EXISTS AT ALL, MEASURED 2026-10-04, AND NOT FOR THE PLAN'S REASON
     *
     * The plan's Task 2 asked for it because the live run raised `1062 Duplicate entry …
     * for key 'tf_users.PRIMARY'`. That raise is GONE: `placeRow()` now resolves a repeat
     * with one atomic `upsert()`, and on the covered-key path no duplicate can be raised at
     * all. Measured across the WHOLE shipped schema, not inferred from `tf_users`:
     *
     *  - Every migration in `src/Laravel/Migrations/` was executed against sqlite and the 36
     *    tables named by `src/Schema/schema/mirror-catalog.json` were introspected with the
     *    very `getIndexes()` call `primaryKeyColumns()` makes: **36/36 carry a discoverable
     *    primary key and 36/36 declare NO non-primary unique index.** There is no second
     *    identity for a repeat to collide on.
     *  - MySQL cannot raise one from `upsert()` regardless: `MySqlGrammar::compileUpsert()`
     *    (vendor/laravel/framework/src/Illuminate/Database/Query/Grammars/MySqlGrammar.php:426)
     *    **discards `$uniqueBy`** and emits `on duplicate key update`, which fires on ANY
     *    unique key. postgres and sqlite keep the conflict target, but on those two drivers
     *    the measurement above says there is no other unique index to violate.
     *
     * ## THE PATH THAT IS STILL REACHABLE, and `upsert()` did not touch it
     *
     * `placeRow()` has a second branch, unchanged by that fix:
     *
     *     if ($primary === null) { $db->table($table)->insert($row); return null; }
     *
     * `$primary` is null whenever the table's identity cannot be introspected, which is
     * `primaryKeyColumns()`'s own documented fallback ("cannot introspect — let the insert
     * raise the real error"). On that branch nothing converges a repeat, the database still
     * enforces the PRIMARY, and the second row of an identity raises — taking every
     * unrelated row queued behind it out of `write()`, and out of the page with it through
     * `MirrorIngesterFactory`'s transaction.
     *
     * It is an ordinary condition, not a contrived one: `primaryKeyColumns()` swallows ANY
     * `QueryException` from `getIndexes()`, and index introspection is an ordinary thing to
     * lose — a restricted `information_schema` / `pg_catalog` grant, a proxy that filters
     * catalog reads. The case is pinned on the default engine by
     * `tests/Schema/Mirror/RepeatedEntityConvergesTest::test_a_duplicate_key_costs_one_row_not_the_page`,
     * which denies `getIndexes()` on an otherwise entirely real connection.
     *
     * ## PER DRIVER, MEASURED — never guessed
     *
     *  - **mysql** 1062 ER_DUP_ENTRY, an INT in `errorInfo[1]` — the same slot and the same
     *    int shape as the four codes already measured in this file. NOT VERIFIED ON A LIVE
     *    MYSQL BY ME: this repository carries no MySQL credentials and must not acquire any,
     *    so the live-MySQL gate for the 1062 shape is the operator's to run.
     *  - **postgres** `23505` (`unique_violation`), measured on the live postgres 17 over
     *    unix-socket peer auth:
     *      errorInfo = ["23505", 7, 'ERROR:  duplicate key value violates unique constraint …']
     *    Note slot 1 is the STRING `"7"` (pq's errorCode), not an int — which is exactly why
     *    the `is_int` guards below are load-bearing and not decoration.
     *  - **sqlite** `19` (`SQLITE_CONSTRAINT`) AND the phrase `UNIQUE constraint failed`,
     *    measured: errorInfo = ["23000", 19, 'UNIQUE constraint failed: tf_users.account_id,
     *    tf_users.id']. Both halves are required. Code 19 alone is every sqlite constraint
     *    (NOT NULL, FOREIGN KEY and CHECK raise it too), and state `23000` alone is every
     *    sqlite integrity violation — so neither may classify alone, and this file already
     *    pins that sqlite's generic code must not be swallowed
     *    (`tests/Ingest/MirrorFactWriterValueNotStorableDegradesTest::test_a_bare_sqlite_constraint_failure_still_raises`).
     *
     * ## A CORRECTION this measurement forced on the comments above — APPLIED 2026-10-04
     *
     * Four comments in this file said SQLite's SQLSTATE "is `HY000` for every error it ever
     * raises" / "its code for everything". That is FALSE for integrity violations, and the
     * false claim is now removed from every one of those sites rather than left standing
     * here while this passage denies it — a file that asserts a thing and then denies it
     * teaches a reader neither. Each site now states the measurement:
     *
     *  - `UNKNOWN_COLUMN_SQLSTATES` — HY000 is the GENERIC state, not the universal one.
     *  - `VALUE_NOT_STORABLE_SQLSTATES` — no sqlite arm exists, and no sqlite STATE could
     *    key it: `23000`/19 for integrity, `HY000`/1 for everything else, so neither names
     *    this fault.
     *  - `placeRow()` — the primary key is read from the shipped DDL because sqlite's
     *    SQLSTATE names no fault precisely, which is a different reason from the false one.
     *  - `isUnknownColumn()` — the HY000 branch is reached only by the non-integrity
     *    family; an integrity violation is `23000` and cannot reach it.
     *
     * `isUnknownColumn()` was always safe on the loose claim because it additionally
     * requires a phrase an integrity violation never contains — so this correction changes
     * NO behaviour. What it removes is the trap in the general statement: a new arm built
     * on "sqlite reports HY000 for everything" would have classified every sqlite integrity
     * violation.
     *
     * Measured on sqlite 3.46.1 via an in-memory capsule from
     * `tests/Store/ShippedStoreSchema::capsuleFor()`: missing table, unknown column and
     * syntax fault all `HY000`/1; UNIQUE and NOT NULL (omitted and explicit NULL, INTEGER
     * and TEXT columns) all `23000`/19.
     *
     * ## A SECOND, INDEPENDENT SIGNAL — and why the arm needs BOTH
     *
     * This file already has a precedent for reading a driver's own verdict rather than
     * pattern-matching it: the value/column arm "injects the MySQL driver code at the
     * connection boundary — the one layer where a real driver supplies its own code". The
     * framework does the same thing one layer up, in `Connection::runQueryCallback()`
     * (vendor/laravel/framework/src/Illuminate/Database/Connection.php:853): when a driver
     * raises a unique violation Laravel raises `UniqueConstraintViolationException` instead
     * of a plain `QueryException`, deciding that with a PER-DRIVER test of its own —
     *
     *   - MySqlConnection.php:80    `preg_match('#Integrity constraint violation: 1062#i', …)`
     *   - PostgresConnection.php:78 `'23505' === $exception->getCode()`
     *   - SQLiteConnection.php:60   `UNIQUE constraint failed: …` or `column(s) … is not unique`
     *
     * So this arm requires the framework's typed verdict AND the measured code/state above.
     * Both, not either, and the reason is precision rather than caution: the codes alone
     * name a FAMILY on sqlite, while the typed class alone would trust a framework version
     * bump. Two independent signals that agree are a fact; either one alone is an assumption.
     *
     * A measured CONSEQUENCE worth recording, because it reads like a contradiction and is
     * not: `tests/Ingest/MirrorFactWriterValueNotStorableDegradesTest::test_a_bare_sqlite_constraint_failure_still_raises`
     * injects a bare `QueryException` carrying sqlite's code 19 and a UNIQUE-flavoured
     * message, and still asserts it re-raises. It passes, and it now asserts something
     * STRONGER than it did before: bare code 19 without the driver's own typed verdict still
     * re-raises, because that double bypasses the real driver and Laravel's classification
     * never ran. It is deliberately left untouched.
     *
     * @var list<int>
     */
    private const UNIQUE_VIOLATION_MYSQL_CODES = [1062];

    /** @var list<string> */
    private const UNIQUE_VIOLATION_SQLSTATES = ['23505'];

    /**
     * sqlite's `SQLITE_CONSTRAINT` driver code, which on its own means every constraint.
     *
     * @var list<int>
     */
    private const SQLITE_CONSTRAINT_CODES = [19];

    /**
     * The sqlite phrase that narrows `SQLITE_CONSTRAINT` to UNIQUENESS.
     *
     * Only the phrase measured above is claimed. sqlite's other wording for the same family
     * was not observed, so it is not listed — a phrase nobody has seen classify nothing, and
     * a phrase that was guessed could swallow a fault it does not name.
     *
     * @var list<string>
     */
    private const SQLITE_UNIQUE_PHRASES = ['UNIQUE constraint failed'];

    /**
     * Place a list of decomposed rows into the database.
     *
     * ## `inserted` COUNTS STATEMENTS PLACED. IT IS NOT A COUNT OF ROWS HELD.
     *
     * CORRECTED 2026-10-04. This paragraph used to describe `inserted` as a
     * count of rows placed, to say that a value therefore asserts the mirror
     * holds the fact once the upsert has run, and to name a watermark advance
     * and an emitted event as what callers decide it with. All three were false,
     * and the first two were the load-bearing ones — they are the invariant the
     * next reader inherits. The falsehoods are paraphrased rather than quoted
     * here on purpose: `tests/Schema/Mirror/InsertedCountsStatementsNotRowsTest.php`
     * greps this file for the exact sentences, and quoting them would satisfy a
     * gate by keeping the lie in the source.
     *
     * `$inserted++` sits on the line after `placeRow()` returns null, so it
     * counts STATEMENTS THE WRITER PLACED, one per decomposed row that neither
     * declined nor raised. It does not count rows the mirror holds.
     *
     * MEASURED, sqlite, three DISTINCT facts sharing one primary key, one
     * `write()` call. The key measured is
     * `primary(['account_id','message_entity_id'])` — the key
     * `tf_message_entities` shipped until 2026-10-04 — so every entity in one
     * message shared the message's own id:
     *
     *     inserted=3   fkClues=[]   unmappedClues=[]   valueClues=[]
     *     rows held: 1   (constructor=messageEntityItalic — bold and textUrl GONE)
     *
     * Two facts destroyed, three statements counted, and not one of the four
     * buckets says so. Re-delivering the same batch returns the byte-identical
     * report — `inserted=3`, still 1 row — so a converged overwrite is
     * INDISTINGUISHABLE from a fresh insert on every signal this method
     * emits. `MessageActionPeerScopeBiteTest` records the same measurement on
     * `tf_message_actions` with its narrow key.
     *
     * ## WHAT THAT MEASUREMENT IS, AND WHAT IT IS NOT — NARROWED 2026-10-04
     *
     * This paragraph used to say "shipped DDL", which read as ordinary-traffic
     * loss. It is not ordinary traffic, and it cannot be. MEASURED 2026-10-04
     * over ten decoded payload shapes (`message` with and without entities,
     * `messageService`, fwd/reply + entities, `updateNewMessage`,
     * `updateEditMessage`, `updateMessageID`, a two-message page, a `dialog`, a
     * `webPage`): the shipped `MirrorCatalog` + `MirrorFactDecomposer` emit ZERO
     * rows into `tf_message_entities` — it had 0 declared nested sources, no
     * edge reached it — while emitting 18 into the sibling
     * `tf_messages_entities`, which carries `position` in its key and so stores
     * all three. No live update has ever produced the three-facts-one-key batch
     * measured here: the rows are HAND-BUILT by the test, and the collapse is a
     * property of THIS COUNTER, not of any traffic the engine produces.
     *
     * `tf_message_entities` was deleted from the catalog and from the shipped
     * dial on 2026-10-04 for that reason — see the retirement note in
     * `2026_09_29_000100_create_tf_mirror_gap_tables.php`.
     *
     * WHAT SURVIVES THE NARROWING, and is why this warning still stands: the
     * lesson is about `inserted`, and it holds for every table. Any two
     * decomposed rows sharing a primary key produce exactly this report.
     * `tf_messages_entities` is safe today because `position` is IN its key —
     * a property of that table's key, not a guarantee this method offers — so a
     * table whose key drops the discriminator reopens the quiet loss. The gate
     * is kept for exactly that.
     *
     * SO WHAT A CALLER MAY DO WITH IT. `inserted > 0` is a sound predicate for
     * "at least one statement was placed" — `MirrorUpdateIngester::ingest()`
     * gates its `UpdateStored` emit on exactly that and the predicate itself
     * does not lie. What it cannot be is read as a row count, and what NO
     * caller can currently do is detect the loss, because this method's return
     * carries no converge information at all. That is a RETURN-CONTRACT defect
     * and it is not fixed here: `placeRow()` would have to learn whether its
     * `upsert()` updated a row rather than inserting one, the signal would have
     * to be carried across every hop that names the four buckets explicitly,
     * and each consumer would have to be given a policy for "this write
     * converged" — a decision, not a bugfix. Until that exists, treat a
     * non-zero `inserted` as "statements placed", never as "rows held", and
     * never as "nothing was lost".
     *
     * WHAT IS NOT A COUNT OF: it is not a count of INSERT statements either —
     * `placeRow()` upserts on the introspected primary key, so a converged
     * re-delivery is an UPDATE that `inserted` counts all the same.
     *
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     * @return array{
     *     inserted: int,
     *     fkClues: list<string>,
     *     unmappedClues: list<string>,
     *     valueClues: list<string>,
     * }
     *
     * ## `valueClues` must be forwarded by every hop, and every hop now is
     *
     * Adding a key is safe — every reader in the repo indexes this array BY KEY
     * (`$written['fkClues']`), there is no strict destructuring and no whole-array
     * identity assertion anywhere, so nothing breaks. The risk is the opposite:
     * a layer that REBUILDS a narrower array rather than forwarding this one
     * silently DROPS `valueClues`, and the fault it names then disappears at
     * exactly the boundary that was supposed to report it.
     *
     * As of 2026-10-02 the last of those hops is closed, so the standing rule
     * is no longer a to-do list but the reason the ledger exists. Measured, not
     * assumed: the four layers listed here have all been repaired, each naming
     * `valueClues` in its own rebuilt array —
     *
     *   - `src/Teleframe/Ingest/MirrorResultIngester.php` — returns 4 keys
     *   - `src/Teleframe/Ingest/MirrorUpdateIngester.php` — returns 4 buckets
     *   - `src/Teleframe/Ingest/MirrorChunkSync.php` — carries `valueClues`
     *   - `src/Laravel/MirrorIngesterFactory.php` — `buildResultMirror()` names
     *     the key in BOTH arms (the mirrored arm and the not-admitted early
     *     return), so the two branches share one shape
     *
     * and both operator-visible surfaces that read those commands' output now
     * print the reason under its own label AND persist it:
     * `Console/IngestGateCommand.php` fails the run on any non-empty bucket,
     * and `Console/BackfillCommand.php` appends every bucket to
     * `tf_ingest_clues`. `tests/Console/ValueClueReachesOperatorOutputTest`
     * pins the trip to the terminal; `tests/Console/BackfillCommandCluePersistenceTest`
     * pins the trip to the ledger.
     *
     * THE RULE FOR THE NEXT HOP: rebuild the array with all four bucket keys or
     * forward this one whole. `TfIngestClue::recordReport()` takes a report
     * WHOLE for the same reason — it is the only shape from which "I only knew
     * about the bucket I happened to handle" is not expressible.
     */
    public function write(ConnectionInterface $db, array $rows): array
    {
        $inserted = 0;
        $fkClues = [];
        // Its own bucket, not an arm of $fkClues: see the class docblock. The
        // three buckets are three different axes of failure and merging them
        // would make the ledger lie about which one happened.
        $valueClues = [];
        // A clue raised by the search-index hook below, appended to the method's
        // own $unmappedClues and merged into the return. It cannot be a property
        // of its own: write() is re-entrant across batches and a stored clue list
        // would leak one batch's failures into the next batch's return.
        $this->unmappedClues = [];
        $unmappedClues = [];
        /** @var array<string, list<string>> table => column listing, one query per table */
        $columns = [];
        /** @var array<string, list<string>|null> table => primary key columns, one query per table */
        $keys = [];
        /** @var array<string, list<string>> table => the parent tables it has FKs to, one query per table */
        $graph = [];

        // PARENT-FIRST, BEFORE ANY ROW IS PLACED. This is the whole fix for the
        // silently-dropped-child defect described in the class docblock, and it
        // is here — above the loop, not inside it — for two reasons. It must see
        // the WHOLE batch to know which rows are parents of which; and a reorder
        // that happened per-row could not see the parent that has not been
        // reached yet, which is the entire problem. `placeRow()` below is
        // therefore completely unaware that ordering happens, and the three
        // degradation arms it implements mean exactly what they meant before.
        $rows = $this->parentFirst($db, $rows, $graph);

        foreach ($rows as ['table' => $table, 'row' => $row]) {
            if (! isset($columns[$table])) {
                $columns[$table] = $this->columnListing($db, $table);
            }

            $listing = $columns[$table];
            // An EMPTY listing means the table itself is not in the DDL (Laravel
            // returns [] for an unknown table rather than raising). That is a
            // far bigger defect than an unmodelled field — a whole fact table
            // the schema never created — so the filter is NOT applied: the row
            // goes to the insert and the "no such table" error surfaces, as it
            // always did. Only a table that EXISTS gets its unmodelled fields
            // dropped.
            if ($listing === []) {
                $listing = null;
            }

            if ($listing !== null) {
                foreach (array_keys($row) as $column) {
                    if (in_array($column, $listing, true)) {
                        continue;
                    }
                    unset($row[$column]);
                    $unmappedClues[] = "{$table}: column '{$column}' is not in the shipped DDL — field not modelled, skipped";
                }
            }

            if ($row === []) {
                continue; // every field was unmapped — nothing left to place
            }

            try {
                // A null return means the row was PLACED; a string means it was
                // deliberately declined (it could not cover its live primary
                // key) and the string is the clue to report. Declining is not
                // an error, so it is counted as neither `inserted` nor
                // `unmapped`: the mirror does not hold this row, and `inserted`
                // is the one number a caller acts on — `MirrorUpdateIngester::
                // ingest()` gates its `UpdateStored` emit on `inserted > 0` — so
                // counting a declined row would emit an event for a fact that was
                // never stored. The DDL is not missing anything either, so there
                // is no unmapped field to report.
                $declined = $this->placeRow($db, $table, $row, $keys);

                if ($declined !== null) {
                    $fkClues[] = $declined;

                    continue;
                }

                $inserted++;
                $this->indexSearchRow($db, $table, $row);
            } catch (QueryException $e) {
                $message = $e->getMessage();
                if (str_contains($message, 'FOREIGN KEY') || str_contains($message, 'foreign key')) {
                    $fkClues[] = "{$table}: FK constraint failed — {$message}";
                } elseif ($this->isNotNullViolation($e)
                    || str_contains($message, 'NOT NULL')
                    || str_contains($message, 'not null')
                ) {
                    $fkClues[] = "{$table}: NOT NULL constraint failed (missing required fact data) — {$message}";
                } elseif ($this->isUnknownColumn($e)) {
                    $unmappedClues[] = "{$table}: row names a column the shipped DDL does not have — row skipped — {$message}";
                } elseif ($this->isValueNotStorable($e)) {
                    // Placed here, after the substring arms, without changing
                    // their order, and the ordering is a no-op rather than a
                    // coincidence: a 1366/1265/1406/1364 message names a COLUMN
                    // and never says FOREIGN KEY, NOT NULL or "no such column",
                    // so the four conditions are disjoint by construction. What
                    // matters is that this arm comes BEFORE the re-raise and
                    // AFTER them, never instead of one of them.
                    //
                    // It records NOTHING about `inserted`, which is the invariant
                    // that makes degrading safe: `$inserted++` sits above, INSIDE
                    // this try, so a raise from `placeRow()` never reaches it. A
                    // row the mirror does not hold must not be counted as
                    // placed, because `inserted` is the one number a caller
                    // ACTS on: `MirrorUpdateIngester::ingest()` emits
                    // `UpdateStored` on `inserted > 0`, and a declined row that
                    // counted would emit an event for a fact that is not there.
                    // It is its own bucket
                    // rather than a `fkClues` entry because the ledger is read to
                    // find what failed and WHY, and "foreign key constraint
                    // failed" about a binary-encoding fault is a lie that costs
                    // the next reader a whole wrong turn.
                    $driverCode = $e->errorInfo[1] ?? '?';
                    $valueClues[] = "{$table}: value does not fit its column — data truncation, encoding or missing-default fault"
                        ." (driver code {$driverCode}) — row skipped, not stored — {$message}";
                } elseif ($this->isUniqueViolation($e)) {
                    // Placed with the other degradation arms: AFTER the substring arms,
                    // BEFORE the re-raise, never instead of one of them. The ordering is a
                    // no-op rather than a coincidence — a UNIQUE message names no FOREIGN
                    // KEY, no NOT NULL and no missing column, and a 1062 message names no
                    // truncated value — so the conditions are disjoint by construction.
                    //
                    // WHY IT GETS `fkClues` AND NOT A FIFTH BUCKET, which is the decision
                    // this arm had to make and the one worth arguing:
                    //
                    //  - The vocabulary is not new. `placeRow()`'s own decline — the one
                    //    sentence this arm EXTENDS — already lands in `fkClues` via the
                    //    `$fkClues[] = $declined` line above. "This row cannot have an
                    //    identity here" is the same fact as "this row cannot cover its
                    //    primary key", said by the database instead of by the walker.
                    //  - `valueClues` would be a LIE, and an expensive one: the ledger is
                    //    read to find what failed and why, and filing "two rows claim one
                    //    identity" as "the value does not fit its column" sends the next
                    //    reader to the column instead of to identity. They are different
                    //    axes and the file already says so.
                    //  - A fifth bucket is not available from this one file, and shipping
                    //    one anyway would be a defect rather than a design. Measured: eight
                    //    files name each bucket EXPLICITLY — `MirrorIngesterFactory`
                    //    (5 sites), `MirrorChunkSync`, `MirrorUpdateIngester`,
                    //    `MirrorResultIngester`, `UpdateIngestor`, `IngestGateCommand`,
                    //    `BackfillCommand`, `TfIngestClue::BUCKET_*`. A bucket added here and
                    //    not carried by those files is dropped at the FIRST hop, and a clue no
                    //    hop carries is not a report — it is a shrug. This file's own
                    //    docblock states the rule for exactly that case: "rebuild the array
                    //    with all four bucket keys or forward this one whole."
                    //
                    //    CORRECTION, measured 2026-10-04: the previous version of this comment
                    //    also said "Nothing iterates the keys", and that was FALSE. Four sites
                    //    iterate `TfIngestClue::buckets()` — `MirrorIngesterFactory` (the
                    //    `mergeClueReports()` fold and the `hasClues()` predicate),
                    //    `IngestGateCommand` (the gate verdict), `TfIngestClue::recordReport()`
                    //    (the ledger write). Those four would have carried a fifth bucket for
                    //    free. The eight explicit files remain the real cost, so the
                    //    conclusion held; the stated evidence for it did not.
                    //
                    //    AND THE WART IS COSMETIC, which is why no fifth bucket shipped. The
                    //    arm is UNREACHABLE ON THE COVERED-KEY PATH: `placeRow()` converges a
                    //    primary-key repeat with one atomic `upsert()` (the same fact twice
                    //    yields `inserted: 2`, zero clues), and no table the writer feeds
                    //    carries a non-primary UNIQUE index.
                    //
                    //    MEASURED 2026-10-04 over the WHOLE shipped schema, not a sample of
                    //    it, by executing EVERY file in src/Laravel/Migrations/ in filename
                    //    order against sqlite (:memory:) and reading getIndexes() per table:
                    //    270 tables, of which 6 declare 7 unique indexes beyond their primary
                    //    key (telegram_accounts ×2, telegram_apps, tf_backfill_cursors,
                    //    tf_store_backup_runs, tg_update_routing, tl_user_bindings) — and
                    //    NONE of those 6 tables is named by mirror-catalog.json, whose 36
                    //    tables every production caller feeds. So the covered-key path has no
                    //    second identity to collide on.
                    //
                    //    The migration-file COUNT is deliberately NOT quoted here. It moves with every
                    //    added migration and a catalogued migration, and during the 2026-10-04
                    //    lane wave it was observed at 41, then 42, then 43 within the same
                    //    afternoon, with the catalogued table count moving 36 → 35 and the
                    //    total 270 → 269 as sibling lanes committed. A count in a "MEASURED"
                    //    line that a sibling lane invalidates before the ink dries is a
                    //    defect, which is exactly what the "41 files" this comment first
                    //    carried had become. Re-run the command above and re-read the counts;
                    //    the PROPERTY — zero overlap between the two sets — is what the gate
                    //    in tests/Schema/Mirror/RejectedRowCostsOneRowTest.php asserts, and
                    //    that gate re-measures the live schema on every run rather than
                    //    trusting any number written here.
                    //
                    //    A previous version of this comment said "92 tables across the three
                    //    shipped mirror-fact migrations carry ZERO non-primary UNIQUE
                    //    indexes". 92 was not reproducible from any principled definition of
                    //    "the three shipped mirror-fact migrations" (of the 17 migrations
                    //    that create tables, only two different triples sum to 92, and
                    //    neither is a natural grouping), and it covered roughly a third of
                    //    the 270 tables that actually ship. The number is replaced by the
                    //    survey above; the property it claimed survives a wider survey, so
                    //    only the evidence was wrong.
                    //
                    //    REACHABILITY, stated precisely because "unreachable" was too strong:
                    //    the arm DOES fire on `placeRow()`'s bare-insert branch, taken when
                    //    `primaryKeyColumns()` returns null (:1318-1325). That is not
                    //    hypothetical — `test_a_duplicate_key_costs_one_row_not_the_page`
                    //    reproduces it — and it is the ONE reachable path this arm covers.
                    //    On the shipped schema no catalogued table reaches that branch: all
                    //    36 expose a discoverable primary key. The branch is reachable
                    //    because introspection can be LOST (a restricted catalog grant, a
                    //    proxy filtering catalog reads), not because the DDL asks for it.
                    //
                    //    WHERE IT DOES FIRE, the clue BODY discriminates — "row cannot cover
                    //    its own identity — the database already holds this key" plus the
                    //    driver's "Duplicate entry ... for key ..." — so it is never
                    //    byte-identical to a genuine missing parent's clue at any of the three
                    //    terminal surfaces, and nothing filters on the `bucket` column.
                    //
                    //    And the property is now GATED rather than asserted in prose:
                    //    tests/Schema/Mirror/RejectedRowCostsOneRowTest.php executes the
                    //    migrations and fails the day a catalogued table gains a second
                    //    unique index.
                    //
                    // So the honest cost is recorded rather than hidden: the bucket's NAME
                    // says foreign key, and a UNIQUE violation is not one. That wart is
                    //    pre-existing and shared — MySQL's 1048 not-null violation is already
                    //    filed here for the same reason — and it would stop being cosmetic the
                    //    moment a mirror table gained a non-primary unique index, or a surface
                    //    alerted on `bucket` instead of printing the body.
                    //
                    // It records NOTHING about `inserted`, which is the invariant that makes
                    // degrading safe: `$inserted++` sits above, INSIDE this try, so a raise
                    // from `placeRow()` never reaches it. A row the mirror does not hold must
                    // not be counted as placed, because `inserted` is the one number a caller
                    // ACTS on — `MirrorUpdateIngester::ingest()` emits `UpdateStored` on
                    // `inserted > 0`.
                    //
                    // AND NOT COUNTING IT IS NOT THE SAME AS DETECTING THE LOSS. This arm
                    // is loud, so the row is recoverable. A CONVERGED overwrite is silent:
                    // measured on the shipped DDL, three facts sharing one primary key give
                    // `inserted=3`, four empty clue buckets and one stored row. Nothing in
                    // this return can tell that apart from three clean inserts. See
                    // `write()`'s own docblock — that is a return-contract defect, not a
                    // bug in this arm.
                    $fkClues[] = "{$table}: row cannot cover its own identity — the database already holds this key"
                        .' — row skipped, not stored — '.$message;
                } else {
                    throw $e;
                }
            }
        }

        $unmappedClues = [...$unmappedClues, ...$this->unmappedClues];
        $this->unmappedClues = [];

        return [
            'inserted' => $inserted,
            'fkClues' => $fkClues,
            'unmappedClues' => $unmappedClues,
            'valueClues' => $valueClues,
        ];
    }

    /**
     * Re-order a decomposed batch so every row's parent precedes it.
     *
     * Returns the caller's rows UNCHANGED when the batch is trivially ordered,
     * when no table in it carries a foreign key, or when the schema cannot be
     * introspected. That fallback is the point: the input order is what shipped
     * before this method existed, so "I could not see the graph" degrades to
     * exactly today's behaviour and never to something worse.
     *
     * ## WHY A SORT AND NOT A RETRY LOOP
     *
     * The tempting alternative is to re-try a row once at the end of the batch,
     * which is cheaper to write and strictly worse: it only helps when the
     * parent happens to land in the SAME batch, it turns one insert into two on
     * every out-of-order row, and — worst — the rows in between still observe
     * a half-built batch. A sort fixes the batch for every row in it at once.
     *
     * ## WHY THE GRAPH IS OVER ROWS AND NOT OVER TABLES
     *
     * Over tables would be simpler and would be WRONG in a way that costs real
     * data. A child row is satisfied by a PARENT ROW, not by a parent table: if
     * a batch holds two messages and one media child, "put all of tf_messages
     * before all of tf_messages_media" is a statement about tables that
     * happens to be harmless, but "put every child of message 2 before message
     * 1" is a table-level rule with no row-level meaning, and a rule that
     * cannot be expressed at row level cannot be made STABLE — which is the
     * property below that keeps already-ordered input byte-identical.
     *
     * ## WHY IT TERMINATES — the property an unbounded sort would not have
     *
     * A relation graph can contain a cycle, and a naive topological sort on a
     * cycle does not produce a wrong answer; it produces NO answer, forever,
     * inside the ingest transaction. That is strictly worse than the bug this
     * method fixes: a hang takes the page down, whereas the bug loses one row
     * quietly. So the loop below is bounded by construction rather than by
     * hope:
     *
     *  - the ascending scan `$scan` is monotonically increasing and never
     *    rewinds, so it visits each of the `$count` rows at most once;
     *  - a row is emitted at most once, because it is emitted either by the
     *    scan or by a stack pop, and a row reaches the stack only by being
     *    dequeued from exactly one `$deferred[$blocker]` bucket;
     *  - a parent table hands its waiters to the stack only on the transition
     *    `$remaining[$table] === 0`, which happens once per table.
     *
     * The only exit that is not "every row emitted" is the CYCLE EXIT: the scan
     * is exhausted, the stack is empty, and `$deferred` still holds rows. Those
     * rows are mutually unorderable — no sequence places all of them after
     * their parents — so they are emitted in the CALLER'S OWN ascending index
     * order, deterministically, with no unblocking pass at all (which is what
     * keeps a row from being emitted twice). Whatever the database then refuses
     * is refused for the real reason, and lands in `fkClues` exactly as it did
     * before this method existed. The cycle degrades; it does not hang.
     *
     * ## WHY ALREADY-ORDERED INPUT COMES BACK BYTE-FOR-BYTE
     *
     * The scan emits a row directly into the output the moment it is placeable,
     * in ascending index order, and a deferred row is emitted later by a stack
     * pop. So when the input is already parent-first NOTHING is ever deferred,
     * the stack is never used, and the output is the input array unchanged.
     * Stability is therefore not a hope about the algorithm's tie-breaking; it
     * falls out of placing every already-valid row on the direct path. (A
     * min-heap over "currently placeable rows" would also be stable, but only
     * if it is fed correctly — and the direct path cannot get it wrong.)
     *
     * ## WHY IT IS LINEAR, not quadratic
     *
     * A row registers under exactly ONE blocking parent — the first of its
     * declared parents that still has un-emitted rows — and is re-checked only
     * when that one table empties. So the total work is O(rows × parents per
     * table), with the parents-per-table factor being the FK count on a single
     * table (one or two, everywhere in the mirror). The naive alternative —
     * building the full row-to-row edge set — is O(rows²) on a self-referencing
     * table such as `tf_channel_participants`, whose parent/child link to
     * itself is shipped by the `participantLink()` Blueprint helper. That is
     * the shape a hostile input would arrive in, and it is the shape this
     * avoids.
     *
     * @param  list<array{table: string, row: array<string, int|string|null>}>  $rows
     * @param  array<string, list<string>>  $graph  per-table parent cache, by reference
     * @return list<array{table: string, row: array<string, int|string|null>}>
     */
    private function parentFirst(ConnectionInterface $db, array $rows, array &$graph): array
    {
        $count = count($rows);
        if ($count < 2) {
            return $rows;
        }

        /** @var array<string, list<string>> $parents table => the parent tables its rows wait for */
        $parents = [];
        /** @var array<string, int> $remaining table => rows of it not yet emitted */
        $remaining = [];
        $constrained = false;

        foreach ($rows as $row) {
            $table = $row['table'];
            $remaining[$table] = ($remaining[$table] ?? 0) + 1;
            if (! isset($parents[$table])) {
                $parents[$table] = $this->parentTables($db, $table, $graph);
            }

            if ($parents[$table] !== []) {
                $constrained = true;
            }
        }

        // Nothing in this batch carries an FK, so there is no parent to wait
        // for and no order to fix. Returning here is what keeps the common case
        // — a facts-only batch, or a host whose schema cannot be introspected —
        // a pure array copy.
        if (! $constrained) {
            return $rows;
        }

        /** @var list<array{table: string, row: array<string, int|string|null>}> $output */
        $output = [];
        /** @var list<int> $stack row indexes that became placeable AFTER the scan passed them */
        $stack = [];
        /** @var array<string, list<int>> $deferred parent table => rows blocked on it, ascending */
        $deferred = [];
        $scan = 0;

        while (true) {
            // Pass 1 — drain rows the scan already unblocked. LIFO is deliberate
            // and is NOT a fairness or ordering claim: it only decides the order
            // of rows that were all out of place to begin with, which is exactly
            // the input class this method is free to re-order. `array_pop` keeps
            // it O(1) per row.
            while ($stack !== []) {
                $index = array_pop($stack);
                $table = $rows[$index]['table'];
                $output[] = $rows[$index];
                $remaining[$table]--;
                if ($remaining[$table] === 0) {
                    // This table has no un-emitted rows left, so everything
                    // parked behind it gets one free re-check. Fired once per
                    // table — the transition above cannot recur.
                    foreach ($deferred[$table] ?? [] as $waited) {
                        $stack[] = $waited;
                    }
                    unset($deferred[$table]);
                }
            }

            // Pass 2 — walk the caller's rows in their own order, emitting each
            // one the moment no parent row of its table is still outstanding,
            // and parking the rest behind the first parent that is.
            //
            // Pass 2 runs AFTER the drain above, not interleaved with it, and
            // that is what makes already-ordered input stable: a batch that is
            // already parent-first never parks anything, so every row takes the
            // direct path below and lands in the caller's own sequence. Rows
            // parked here are unblocked by a LATER emission in this same pass and
            // are drained on the next turn of the loop — which is why the exit
            // below tests `$stack` BEFORE it tests `$deferred`: a non-empty
            // stack means there is work in flight, not a cycle.
            while ($scan < $count) {
                $table = $rows[$scan]['table'];
                $blocker = null;
                foreach ($parents[$table] as $parent) {
                    if (($remaining[$parent] ?? 0) > 0) {
                        $blocker = $parent;
                        break;
                    }
                }

                if ($blocker === null) {
                    // The direct path. This is the branch every row of an
                    // already-parent-first batch takes, in order, which is why
                    // that input is reproduced unchanged.
                    $output[] = $rows[$scan];
                    $remaining[$table]--;
                    if ($remaining[$table] === 0) {
                        foreach ($deferred[$table] ?? [] as $waited) {
                            $stack[] = $waited;
                        }
                        unset($deferred[$table]);
                    }
                } else {
                    $deferred[$blocker][] = $scan;
                }

                $scan++;
            }

            // The scan is monotonic and the drain terminates, so this loop runs
            // at most twice on any input: once to scan, once to drain what the
            // scan unblocked. There is no third turn, because pass 2 cannot run
            // again with work left and pass 1 cannot push onto its own stack
            // without emptying a table first.
            if ($stack !== []) {
                continue;
            }

            // Scan exhausted, stack drained, and rows are STILL parked: the
            // relation graph they wait on contains a cycle. No ordering of them
            // satisfies every FK, so they go out in the caller's own order —
            // deterministically, and with the unblocking pass switched off so
            // that a row parked behind a table which empties during this drain
            // cannot be emitted a second time.
            if ($deferred === []) {
                break;
            }

            $stuck = [];
            foreach ($deferred as $parked) {
                foreach ($parked as $index) {
                    $stuck[] = $index;
                }
            }
            sort($stuck); // ascending input index — deterministic for ANY input
            foreach ($stuck as $index) {
                $output[] = $rows[$index];
            }

            return $output;
        }

        return $output;
    }

    /**
     * The parent tables `table` declares foreign keys to, in declaration order
     * and de-duplicated, or `[]` when the schema cannot say.
     *
     * ## WHY THE DATABASE IS ASKED, THREE TIMES OVER IN THIS FILE
     *
     * `columnListing()` and `primaryKeyColumns()` already read the DDL rather
     * than guess at it, for the same reason this method does: the migrated
     * schema is the only copy that cannot drift from the code. The alternatives
     * are each measurably wrong here, which is worth recording because they all
     * look like the cheap option:
     *
     *  - **A hardcoded parent-before-child table list.** It is the shape this
     *    change was most likely to be written as, and it is wrong the day a
     *    migration adds a FK, in exactly the silent direction — a missed edge
     *    drops a row without a word. The mirror already has ~45 tables and three
     *    curated cross-domain FKs over them; the list would be a 45th fact to
     *    forget.
     *  - **`grep "->foreign("` over `src/Laravel/Migrations/`.** Undercounts,
     *    because FKs are emitted from shared `Blueprint` helpers — `linkChild`,
     *    `participantLink`, `channelUpdateLink`, `differenceLink`, `factKey` —
     *    whose only statement of WHICH TABLE is the child is the `$table`
     *    argument at the call site. A grep sees the helper definitions, not the
     *    call sites, so it under-reports the very edges that matter.
     *  - **`mirror-catalog.json`'s `children`.** That is a TL-CONSTRUCTOR field
     *    map — `["media", "FK→MessageMedia"]`, `["entities", "1:N child"]` —
     *    and it never names a relational table at all, so it cannot be the
     *    source of a table-level graph without a TL-type-to-table mapping that
     *    does not exist and is not this change's business to invent.
     *
     * `Builder::getForeignKeys()` answers on sqlite, MySQL and Postgres from one
     * call each, and it answers about the schema THIS connection actually has,
     * including anything a host's own migration added.
     *
     * `[]` is the honest answer for a connection that is not an Illuminate
     * `Connection`, for a table the driver will not describe, and for a table
     * that does not exist — all three leave `parentFirst()` returning the
     * caller's order, which is the behaviour that shipped before it existed.
     *
     * @param  array<string, list<string>>  $graph  per-table cache, by reference
     * @return list<string>
     */
    private function parentTables(ConnectionInterface $db, string $table, array &$graph): array
    {
        if (isset($graph[$table])) {
            return $graph[$table];
        }

        $parents = [];

        if ($db instanceof Connection) {
            try {
                foreach ($db->getSchemaBuilder()->getForeignKeys($table) as $foreignKey) {
                    // `foreign_table` is non-nullable in Laravel's own shape
                    // and every driver processor that builds this array fills
                    // it, so it is read straight rather than guarded. The `''`
                    // arm below is the one that can still arrive: an FK
                    // declared against no table is not something these drivers
                    // produce, and a table name of `''` would match nothing in
                    // `$remaining` anyway, so it is skipped rather than parked
                    // behind a parent that does not exist.
                    $parent = $foreignKey['foreign_table'];
                    // `in_array` rather than a keyed set: the parent list is one
                    // or two entries long, and declaration order is worth
                    // keeping because it is what decides WHICH parent a parked
                    // row waits on — so the order has to be the schema's, not a
                    // hash's.
                    if ($parent !== '' && ! in_array($parent, $parents, true)) {
                        $parents[] = $parent;
                    }
                }
            } catch (QueryException) {
                $parents = []; // cannot introspect — keep the caller's order
            }
        }

        return $graph[$table] = $parents;
    }

    /**
     * Index a just-inserted tf_messages row into tf_message_search.
     *
     * Without this the BACKFILL path would populate tf_messages while leaving
     * the search index permanently empty — and since backfill is how a new host
     * ingests its whole history, the search would be dead for exactly the
     * accounts that have the most to search. The live path (UpdateIngestor)
     * indexes independently; both writers must, or the index only ever reflects
     * whichever one ran.
     *
     * Deliberately a no-op on any other table, and deliberately silent on
     * failure: the search index is a DERIVED structure, so a failure to build it
     * must not fail the ingest of the real fact that just succeeded. It is
     * reported as a clue instead, which is the same channel every other
     * tolerated problem already uses.
     *
     * The table-existence check runs once per writer instance (this is a
     * long-lived singleton on the ingest path) so a host that has not migrated
     * the index yet pays one probe, not one per row.
     */
    private function indexSearchRow(ConnectionInterface $db, string $table, array $row): void
    {
        if ($table !== self::SEARCHABLE_TABLE || ! array_key_exists('message', $row)) {
            return;
        }

        if ($this->searchIndex === false) {
            return;
        }

        if ($this->searchIndex === null) {
            $present = false;
            try {
                $present = $db instanceof Connection
                    && $db->getSchemaBuilder()->hasTable('tf_message_search');
            } catch (QueryException) {
                $present = false; // cannot introspect — assume absent, never block ingest
            }
            $this->searchIndex = $present ? new MessageSearchIndexer : false;
        }

        if ($this->searchIndex === false) {
            return;
        }

        try {
            // The peer legs travel with the id, because the index key is the
            // MESSAGE's peer-scoped key and not the id alone: `index()` replaces
            // every token row for the message it is given, so passing a bare id
            // deleted every other dialog's tokens for that id and left those
            // messages unfindable. Both values are already on the row — they are
            // half its primary key — so this reads them rather than recomputing
            // anything.
            $this->searchIndex->index(
                (int) ($row['account_id'] ?? 0),
                (int) ($row['id'] ?? 0),
                (string) $row['message'],
                (int) ($row['peer_type'] ?? 0),
                (int) ($row['peer_id'] ?? 0),
            );
        } catch (QueryException $e) {
            $this->unmappedClues[] = 'tf_message_search: token index write failed for message '
                .($row['id'] ?? '?').' — search will omit it until reindexed: '.$e->getMessage();
        }
    }

    /**
     * Place ONE decomposed row: converge on re-delivery, insert otherwise.
     *
     * A mirror row is a FACT, and the bus is at-least-once, so the same fact
     * arriving twice is ordinary rather than exceptional: a crash mid-batch
     * replays the un-acked tail, `XAUTOCLAIM` steals a merely-slow worker's
     * entries after 60s, and `replayDeadLetter()` re-publishes on purpose. The
     * write therefore CONVERGES instead of raising — ONE atomic `upsert()` on the
     * table's real primary key, which is the same choice the curated live path
     * already makes at `UpdateIngestor::ingestImpl` (`updateOrInsert`).
     *
     * WHY UPSERT AND NOT INSERT-OR-IGNORE, which are different products: an
     * edit arrives as a SECOND `message` fact for an id already in the mirror
     * (`updateEditMessage` and `updateEditChannelMessage` are both in
     * `MirrorIngesterFactory::UPDATE_FACT_FIELD`), so under insert-or-ignore
     * the mirror would keep the pre-edit text and the pre-edit `edit_date`
     * forever, with nothing to correct it. That is the operator's own sentence
     * — verbatim 2026-09-14: "what ever comes as telgram update has been
     * procced in telgram and its now a fact that tegram has this data and gave
     * it to us as update so this update absolutely should be added to our
     * database" — read literally: an edit IS an update, and "absolutely should
     * be added" is not satisfiable by a no-op.
     *
     * The key is read from the SHIPPED DDL (`getIndexes()`), never guessed and
     * never pattern-matched out of driver error text — the same
     * ask-the-database discipline `columnListing()` already uses, for the same
     * reason: on SQLite the SQLSTATE names no fault precisely enough to key on —
     * measured on sqlite 3.46.1, a missing table, an unknown column AND a syntax
     * fault all report the one generic `HY000`/1, while an integrity violation is
     * the separate `23000`/19.
     * The mirror's own primaries are composite `(account_id, id)`, which is why
     * the key is a list and not a single column.
     *
     * A table with no discoverable primary key still bare-inserts, because
     * there is no identity to fabricate and none to violate. A row that CANNOT
     * cover its live primary key does NOT: see the long note below — that row
     * is skipped and reported, never written under an invented key.
     *
     * ## WHY A BARE INSERT HERE IS A LIE, and not a harmless fallback
     *
     * The bare insert used to be this method's answer to an uncoverable key.
     * It looks safe and is not, and the reason it looks safe is the exact
     * reason it is dangerous: whether it fails depends on a COLUMN DEFAULT
     * that has nothing to do with correctness.
     *
     * Measured against the real shipped migration
     * `2026_09_14_200004_create_tf_dialogs_tables.php`, `tf_dialogs` is
     * `primary(['account_id','peer_type','peer_id'])` and BOTH peer columns are
     * `NOT NULL` **with `->default(0)`**. So a `dialog` whose payload carries
     * no `peer` cannot be upserted — and cannot be refused either. The insert
     * SUCCEEDS, and the mirror acquires a dialog keyed on a peer that does not
     * exist: `peer_type=0, peer_id=0`. Meanwhile the decomposer's own clue in
     * the ledger reads "peer column 'peer' missing from payload — cannot place
     * the fact", because `MirrorFactDecomposer` deliberately refuses to write
     * 0/0 for an unresolvable peer "instead of silently writing 0/0 into the
     * mirror". The two halves of this codebase contradicted each other, and the
     * column default won, one layer down.
     *
     * `tf_channel_participants` hid this for months: same missing key column,
     * but `channel_id` carries no default, so the NOT NULL violation raised
     * and `write()` caught it into a clue. The row was refused BY ACCIDENT —
     * by a default, not by a decision. Any table whose key column gains a
     * default silently flips from "degrades" to "stores a lie".
     *
     * And a fabricated key is not merely unaddressable, it is ACTIVELY HOSTILE:
     * every later peerless dialog collapses onto the same `(account_id,0,0)`
     * and the SECOND one raises `UniqueConstraintViolation` on the very key
     * that was invented for it. `write()` classifies FK, NOT NULL and
     * unknown-column only, so a UNIQUE violation re-raises out of the loop and
     * takes the entire ingest transaction — and every unrelated row queued
     * behind it — with it. One missing peer on one dialog is not a per-row
     * problem.
     *
     * So the row is SKIPPED, and the skip is returned as a clue rather than
     * thrown: `placeRow()` returns the sentence to report, or null when it
     * placed. Skipping keeps the rest of the batch alive, and `write()` does
     * not increment `inserted` for a row the mirror does not hold — because
     * `inserted` is the one number a caller acts on
     * (`MirrorUpdateIngester::ingest()` emits `UpdateStored` on `inserted > 0`),
     * and a declined row that counted would emit an event for a fact the mirror
     * does not have. It is the loud half of "not held", not the whole of it: a
     * converged overwrite is silent, and no field of this return reports it.
     *
     * @param  array<string, int|string|null>  $row
     * @param  array<string, list<string>|null>  $keys  per-table primary key cache, by reference
     * @return string|null the skip clue when the row could not be placed, null when it was
     */
    private function placeRow(ConnectionInterface $db, string $table, array $row, array &$keys): ?string
    {
        if (! isset($keys[$table])) {
            $keys[$table] = $this->primaryKeyColumns($db, $table);
        }

        $primary = $keys[$table];

        if ($primary === null) {
            // No discoverable identity at all: there is nothing to fabricate and
            // nothing to violate, so a bare insert stays legitimate here and the
            // insert still raises whatever the table really objects to.
            $db->table($table)->insert($row);

            return null;
        }

        $key = [];
        $uncovered = [];
        foreach ($primary as $column) {
            if (! array_key_exists($column, $row)) {
                // Collected rather than returned on first miss: the operator
                // reading the ledger needs the WHOLE uncovered set, and one
                // clue per row is the per-row budget this writer works to.
                $uncovered[] = $column;

                continue;
            }

            $key[$column] = $row[$column];
        }

        if ($uncovered !== []) {
            return "{$table}: row cannot cover its primary key (".implode(', ', $primary).')'
                .' — uncovered key columns: '.implode(', ', $uncovered)
                .' — row skipped, not stored under a fabricated key';
        }

        // ONE ATOMIC STATEMENT, NOT AN EXISTENCE CHECK FOLLOWED BY A WRITE.
        //
        // `updateOrInsert($key, $row)` is a TIME-OF-CHECK/TIME-OF-USE pair
        // (vendor/laravel/framework/src/Illuminate/Database/Query/Builder.php:4411):
        // a NON-LOCKING consistent read decides `$exists`, then a LOCKING current
        // read inserts or updates. The page write runs inside a transaction
        // (src/Laravel/MirrorIngesterFactory.php:237,
        // src/Teleframe/Ingest/MirrorChunkSync.php:63) and MySQL's default isolation
        // is REPEATABLE READ, where a consistent read is pinned to the snapshot the
        // transaction took at its first statement while the INSERT is not. A row
        // another writer committed after that snapshot is invisible to the existence
        // check and visible to the INSERT, so the writer concludes "not there", takes
        // the INSERT branch, and the database raises the duplicate key. `SELECT`
        // misses, `INSERT` collides.
        //
        // MEASURED 2026-10-04, and this is the whole of it. The live run stored 39
        // pages and discarded 10, two of them on
        //   Duplicate entry '1724372757-5633995061' for key 'tf_users.PRIMARY'
        // Line 1 of the recorded run (/tmp/nf5-discover.log) is a SECOND writer --
        //   mirror account=1724372757 {method:messages.getSavedDialogs, inserted:537}
        // -- and the first duplicate appears at line 7, BEFORE the first
        // `stored dialogs page` line at line 110. One sequential writer cannot
        // produce that ordering; a concurrent one does.
        //
        // WHAT THIS IS NOT: it is not a key problem. Measured against the shipped DDL,
        // `primaryKeyColumns()` returns exactly ["account_id","id"] for tf_users and
        // for tf_chats, and the derived `$key` at the moment of failure is the very
        // pair the database reported. Nor is it a missing second identity to converge
        // on: `getIndexes('tf_users')` returns exactly ONE index, the PRIMARY, so
        // there is no other unique index a repeat could collide on. Widening the key
        // would have changed a correct key to fix a race.
        //
        // WHY `upsert()` IS THE MINIMAL CHANGE: it is the SAME single call site and
        // the SAME `$key`, reused as the conflict target — `primaryKeyColumns()` is
        // still the only introspection in this path, no second query was added. What
        // changes is only that the conflict is detected by the INSERT itself, which
        // is a locking current read that sees the newest committed row. One
        // statement cannot disagree with itself, so the window closes without
        // touching the transaction boundary, the key, or the surrounding arms.
        //
        // `upsert()` with no `$update` list updates every column of the inserted row
        // (`array_keys(array_first($values))`), which is exactly what
        // `updateOrInsert($key, $row)` did: same columns overwritten, later values
        // winning. `$row` always carries `$primary` at this point — the arm above
        // returns on the first uncovered key column — so every conflict target is
        // present in the values, which is what lets the driver build the statement.
        $db->table($table)->upsert([$row], $primary);

        return null;
    }

    /**
     * The table's real primary key columns, or null when they cannot be
     * introspected (a non-Illuminate connection, or a table the schema builder
     * will not describe).
     *
     * @return list<string>|null
     */
    private function primaryKeyColumns(ConnectionInterface $db, string $table): ?array
    {
        if (! $db instanceof Connection) {
            return null;
        }

        try {
            $indexes = $db->getSchemaBuilder()->getIndexes($table);
        } catch (QueryException) {
            return null; // cannot introspect — let the insert raise the real error
        }

        foreach ($indexes as $index) {
            // Laravel's index processors return plain arrays shaped
            // name/columns/type/unique/primary (not IndexDefinition objects).
            if ($index['primary'] === true && $index['columns'] !== []) {
                return $index['columns'];
            }
        }

        return null;
    }

    /**
     * The columns the live table actually has, or null when the connection
     * cannot be introspected (a non-Illuminate connection implementation).
     *
     * @return list<string>|null
     */
    private function columnListing(ConnectionInterface $db, string $table): ?array
    {
        if (! $db instanceof Connection) {
            return null;
        }

        try {
            return $db->getSchemaBuilder()->getColumnListing($table);
        } catch (QueryException $e) {
            return null; // missing table — let the insert raise the real error
        }
    }

    /**
     * Backstop for the case the pre-filter could not cover (no schema access):
     * does this driver error MEAN "no such column", as opposed to a real
     * constraint violation on a column the DDL does have?
     */
    private function isUnknownColumn(QueryException $e): bool
    {
        $state = (string) ($e->errorInfo[0] ?? $e->getCode());
        if (in_array($state, self::UNKNOWN_COLUMN_SQLSTATES, true)) {
            return true;
        }

        // SQLite only: HY000 is its GENERIC state for the whole non-integrity
        // family — measured on sqlite 3.46.1, a missing table, an unknown
        // column and a syntax fault all report HY000/1, while an integrity
        // violation is the separate 23000/19 and so cannot land here — so the
        // phrase is the only signal there is. Scoped to this exact wording so a
        // NOT NULL / FK / UNIQUE failure — which never says "no column named" —
        // cannot be mistaken for a modelling gap.
        if ($state === 'HY000') {
            $message = $e->getMessage();
            foreach (self::SQLITE_UNKNOWN_COLUMN_PHRASES as $phrase) {
                if (str_contains($message, $phrase)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Does this driver error MEAN "the value does not fit the column it was
     * aimed at", as opposed to a relationship the mirror lacks, a field the DDL
     * does not model, or a genuine whole-batch fault?
     *
     * ## WHY THIS READS THE DRIVER CODE AND NOT `getCode()`
     *
     * MySQL reports 1366/1265/1406/1364 with the SQLSTATE **`HY000`** — the
     * generic "something went wrong" state — and puts the number that actually
     * identifies the fault in `PDOException::$errorInfo[1]`. That matters here
     * more than it usually would, because `HY000` is ALSO a sqlite SQLSTATE:
     * measured on sqlite 3.46.1 it is the state for every NON-integrity fault
     * (missing table, unknown column, syntax fault — all `HY000`/1). Keying this
     * arm off the state would be ambiguous across both drivers: `HY000` is one of
     * MySQL's "value does not fit the column" states AND sqlite's catch-all,
     * while sqlite's integrity family is a DIFFERENT state (`23000`/19) that this
     * arm must never swallow.
     *
     * `getCode()` is no help either — `QueryException::__construct()` copies
     * `$previous->getCode()`, which is `0` for a PDO error, not the driver code.
     *
     * So: slot 1 for the numeric MySQL codes, slot 0 only where the STATE is
     * itself specific (Postgres `22001`). No message substring is consulted,
     * which is what keeps this a targeted arm rather than a loose catch — and
     * the honesty of that claim is pinned by
     * `MirrorFactWriterValueNotStorableDegradesTest::test_a_bare_sqlite_constraint_failure_still_raises`,
     * which asserts sqlite's own generic code `19` is NOT classified.
     */
    /**
     * A genuine NOT NULL violation, matched on the DRIVER CODE rather than on the
     * message — because MySQL's real one does not say the words the message arm
     * looks for.
     *
     * The substring arm above catches `NOT NULL constraint failed`, which is what
     * SQLite and MySQL emit when an INSERT names no value for a NOT NULL column.
     * It does NOT catch MySQL's OTHER not-null failure: **1048**
     * ER_BAD_NULL_ERROR, "Column 'x' cannot be null", which contains neither
     * `NOT NULL` nor `not null` as a phrase. That is a real, live gap — a row
     * carrying an explicit NULL for a NOT NULL column re-raised out of `write()`
     * and took the whole page's transaction with it, the same blast radius as
     * the 1366 this file already handles.
     *
     * It belongs in `fkClues`, not in `valueClues`: 1048 IS a not-null fault, and
     * `fkClues` is where the substring arm above already files those.
     *
     * @var list<int>
     */
    private const NOT_NULL_MYSQL_CODES = [1048];

    private function isNotNullViolation(QueryException $e): bool
    {
        $driverCode = $e->errorInfo[1] ?? null;

        // The `is_int` guard is load-bearing for the same reason as below: without
        // it the string "1048" would match, which is loose matching by another name.
        return is_int($driverCode) && in_array($driverCode, self::NOT_NULL_MYSQL_CODES, true);
    }

    private function isValueNotStorable(QueryException $e): bool
    {
        $errorInfo = $e->errorInfo;
        $driverCode = $errorInfo[1] ?? null;

        // MySQL: the driver code is an int. The `is_int` guard is load-bearing,
        // not decoration — `in_array` without it would let the string "1366"
        // match, which is loose matching by another name.
        if (is_int($driverCode) && in_array($driverCode, self::VALUE_NOT_STORABLE_MYSQL_CODES, true)) {
            return true;
        }

        $sqlState = $errorInfo[0] ?? null;

        return is_string($sqlState)
            && in_array($sqlState, self::VALUE_NOT_STORABLE_SQLSTATES, true);
    }

    /**
     * Does this driver error MEAN "this row cannot have an identity of its own here" — as
     * opposed to a relationship the mirror lacks, a field the DDL does not model, a value
     * that does not fit its column, or a genuine whole-batch fault?
     *
     * The per-driver codes, states and the measured evidence behind each are on the
     * constants above. The shape follows `isValueNotStorable()` exactly: slot 1 for the
     * numeric driver codes, slot 0 only where the STATE is itself specific (postgres
     * `23505`), and sqlite needs BOTH halves of its pair.
     *
     * WHY `errorInfo[1]` IS NEVER READ AS A STATE HERE, measured: on postgres that slot is
     * the STRING `"7"` (pq's errorCode) while the state `23505` is in slot 0 — so a single
     * `is_int` test on slot 1 would silently drop every postgres duplicate while claiming
     * to classify them. The two drivers disagree about which slot holds what, so each is
     * read where it actually lives.
     */
    private function isUniqueViolation(QueryException $e): bool
    {
        // Signal one: the framework's own per-driver verdict, from the layer where a real
        // driver supplies it. Required, not merely nice — see the constant's docblock for
        // why a bare injected code must NOT be enough.
        if (! $e instanceof UniqueConstraintViolationException) {
            return false;
        }

        // Signal two: the measured code/state, read where each driver actually puts it.
        $errorInfo = $e->errorInfo;
        $driverCode = $errorInfo[1] ?? null;
        $sqlState = $errorInfo[0] ?? null;

        // MySQL: 1062 ER_DUP_ENTRY, an int in slot 1 — the same shape as the codes already
        // measured in this file.
        if (is_int($driverCode) && in_array($driverCode, self::UNIQUE_VIOLATION_MYSQL_CODES, true)) {
            return true;
        }

        // postgres: `23505` unique_violation in slot 0. Its driver code is the STRING `"7"`
        // (pq's errorCode), so the `is_int` test above cannot and does not claim it.
        if (is_string($sqlState) && in_array($sqlState, self::UNIQUE_VIOLATION_SQLSTATES, true)) {
            return true;
        }

        // sqlite: `SQLITE_CONSTRAINT` covers every constraint it enforces, so the phrase is
        // required to narrow it to UNIQUENESS. Both halves or nothing.
        if (is_int($driverCode) && in_array($driverCode, self::SQLITE_CONSTRAINT_CODES, true)) {
            $message = $e->getMessage();

            foreach (self::SQLITE_UNIQUE_PHRASES as $phrase) {
                if (str_contains($message, $phrase)) {
                    return true;
                }
            }
        }

        return false;
    }
}
