<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * The writer's degradation contract: a row that cannot cover its live PRIMARY
 * KEY loses only ITSELF, and the batch survives.
 *
 * ## Why this file exists, and what it deliberately does NOT assert
 *
 * `MirrorFactWriter::placeRow()` reads the table's real key from the shipped
 * DDL and upserts on it. When the decomposed row does not carry every column of
 * that key it cannot be addressed on a later delivery, so an upsert would be a
 * lie — the writer therefore falls back to a bare INSERT
 * (`src/Schema/Mirror/MirrorFactWriter.php`), and `write()`'s
 * `catch (QueryException $e)` turns whatever the driver says into a per-row
 * CLUE. That is the contract: DEGRADE, never escalate.
 *
 * An earlier version of this file asserted the opposite. It demanded that this
 * condition RAISE, on the theory that a live PK naming a column the row cannot
 * carry is the fingerprint of an under-declared identity. That reasoning is
 * wrong twice over, and both halves are pinned here so it cannot be
 * reintroduced:
 *
 *   1. A row not carrying a PK column under its own name is NORMAL and
 *      already handled. `MirrorFactDecomposer::keyValue()` supplies a key
 *      column from the catalog entry's expressed derivation (`keyFrom`) when
 *      the constructor does not carry it — `tf_bot_infos.id` from `user_id`,
 *      `tf_saved_star_gifts.id` from the nested `gift.id`. A per-row check
 *      cannot tell "the DDL under-declares this identity" from "this payload
 *      did not carry the key", so it guesses — and it guesses WRONG on both of
 *      those healthy tables. `MirrorDerivedKeyPlacesRowTest` proves that path.
 *   2. It fired INSIDE `placeRow()`, which `write()` guards only against
 *      `QueryException`. A `MirrorSchemaException` ESCAPES the loop and aborts
 *      every remaining row in the batch. A guard that kills ingest is strictly
 *      worse than the silent drop it was meant to surface.
 *
 * The static half of the question — "does the shipped DDL under-declare any
 * catalogued table's identity?" — is a fact about the CATALOG and the
 * MIGRATIONS, not about one payload, and it is checked there, in
 * `tests/Schema/MirrorResolverKeyMatchesShippedDdlTest.php`.
 *
 * Every test here runs the REAL shipped migrations through
 * `ShippedStoreSchema::capsuleFor()` — never a transcribed DDL, because a
 * transcription is a second copy of the thing that drifts.
 */
final class MirrorFactWriterUnplaceableKeyDegradesTest extends TestCase
{
    /**
     * Channel-participants migration. It ships
     * `tf_channel_participants.primary(['account_id','channel_id','user_id'])`
     * while `channelParticipant` (TL) carries no `channel_id` — the coordinate
     * exists only in the enclosing `channels.channelParticipants` vector. So
     * this table GENUINELY cannot cover its live PK from a constructor payload,
     * which makes it the honest instance of the condition under test. It is
     * named and asserted as an open context gap by
     * `MirrorResolverKeyMatchesShippedDdlTest::test_channel_participants_channel_id_is_an_open_context_gap`.
     */
    private const CHANNEL_PARTICIPANTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200031_create_tf_channel_participants_tables.php';

    /** Messages migration — ships a table whose PK every row fully carries. */
    private const MESSAGES_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200010_create_tf_messages_tables.php';

    /**
     * Dialogs migration — ships `primary(['account_id','peer_type','peer_id'])`
     * where BOTH peer columns are `->default(0)`.
     *
     * That default is what makes this table the sharp instance of the defect:
     * the missing key columns are NOT NULL, so the row cannot be refused by the
     * driver — it is ACCEPTED and written keyed on zeros. `tf_channel_participants`
     * above gets away with the same missing column only because `channel_id`
     * happens to carry no default, so the NOT NULL violation is what stops it.
     * Two halves of the same codebase, two different fates, decided by a
     * column default nobody looked at.
     */
    private const DIALOGS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200004_create_tf_dialogs_tables.php';

    /**
     * The bots tables, and the fold that changed `tf_bot_infos` afterwards.
     *
     * Both are named as FILES and applied in order, which is the only way this
     * suite keeps asserting against the schema the package actually ships. See
     * `test_a_key_supplied_by_a_derived_path_places_and_reports_nothing()` for
     * the measurement of what the pre-fold half alone is missing.
     */
    private const BOTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php';

    /**
     * Folds `BotInfo.description` onto the `tf_bot_infos` fact row and drops the
     * fabricated `tf_bot_infos_description` table.
     *
     * The file itself is never edited by this lane, and never needs to be: it is
     * named here so the test boots the POST-fold table the catalog describes.
     */
    private const BOTS_DESCRIPTION_FOLD_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php';

    /** A `dialog` exactly as the wire decodes it: no `peer`, hence no peer columns. */
    private const PEERLESS_DIALOG = [
        '_' => 'dialog',
        'top_message' => 5,
        'unread_count' => 0,
    ];

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    /** A channelParticipant exactly as the wire decodes it: no `channel_id`. */
    private const PARTICIPANT_PAYLOAD = [
        '_' => 'channelParticipant',
        'user_id' => 55,
        'date' => 1735689600,
        'via_request' => true,
    ];

    private const ACCOUNT = 7;

    /**
     * The degradation contract itself: the batch RETURNS.
     *
     * A row that cannot cover its live PK must cost that row and nothing else.
     * `write()` returns a result array — the guard under test is that this
     * return HAPPENS. A raise here, of any class, would propagate out of
     * `placeRow()` past `write()`'s `QueryException`-only handler and abort
     * every row queued behind this one.
     */
    public function test_a_row_that_cannot_cover_its_live_primary_key_does_not_abort_the_batch(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::CHANNEL_PARTICIPANTS_MIGRATION)->getConnection();
            $fact = $this->decompose('tf_channel_participants', self::PARTICIPANT_PAYLOAD);

            // Precondition, asserted rather than assumed: the key really is
            // unfillable from this payload, and the decomposer really does emit
            // the row anyway (it clues AND emits — the writer, not the
            // decomposer, is the last chance to degrade it gracefully).
            self::assertContains(
                "tf_channel_participants: keying column 'channel_id' missing from payload — cannot place the fact",
                $fact['clues'],
                'this fixture only proves anything if the key really is unfillable',
            );
            self::assertSame(
                ['account_id' => self::ACCOUNT, 'constructor' => 'channelParticipant', 'user_id' => 55, 'date' => 1735689600],
                $this->ownRow($fact, 'tf_channel_participants'),
                'the emitted row must genuinely lack the channel_id its live PK demands',
            );

            // The assertion: this call RETURNS. No exception of any class.
            $write = (new MirrorFactWriter)->write($db, $fact['rows']);

            self::assertSame(0, $write['inserted'], 'an unplaceable row must not be counted as inserted');
            self::assertSame(
                0,
                $db->table('tf_channel_participants')->count(),
                'no row may land for a fact whose identity is incomplete',
            );
            self::assertSame(
                [],
                $write['unmappedClues'],
                'an unplaceable key is a write clue, not an unmapped-column clue',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The two failure modes the guard must not have, asserted as ONE property:
     * a row that cannot place is reported as a CLUE, and the rows queued behind
     * it in the same batch still land.
     *
     * The batch is built by hand rather than decomposed so the survivor is a
     * row the shipped DDL fully satisfies. That is the whole point: a guard
     * that raises would take this row down with the bad one, turning one
     * unplaceable fact into a poisoned batch — which is a strictly worse
     * outcome than the per-row clue the old path already produced.
     *
     * The survivor is `tf_messages_reply_markup_rows`
     * (primary(['account_id','id','position'])), which carries no FK to
     * `telegram_accounts`, so this test measures the KEY behaviour and nothing
     * else.
     */
    public function test_an_unplaceable_row_is_reported_as_a_clue_and_later_rows_still_land(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(
                self::CHANNEL_PARTICIPANTS_MIGRATION,
                self::MESSAGES_MIGRATION,
            )->getConnection();

            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_channel_participants', 'row' => ['account_id' => self::ACCOUNT, 'constructor' => 'channelParticipant', 'user_id' => 55]],
                ['table' => 'tf_messages_reply_markup_rows', 'row' => ['account_id' => 1, 'peer_type' => 1, 'peer_id' => 77, 'id' => 5, 'position' => 0, 'constructor' => 'keyboardButtonRow']],
            ]);

            self::assertSame(
                1,
                $write['inserted'],
                'the correctly-keyed row queued behind the unplaceable one must still place',
            );
            self::assertSame(
                1,
                $db->table('tf_messages_reply_markup_rows')->count(),
                'the surviving row must actually be in the table, not merely counted',
            );
            self::assertCount(
                1,
                $write['fkClues'],
                'the unplaceable row must degrade to exactly one per-row clue',
            );
            self::assertStringContainsString(
                'tf_channel_participants',
                $write['fkClues'][0],
                'the clue must name the row that could not place',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The DEFAULTED key column: the writer must refuse to fabricate 0/0.
     *
     * Every other degradation case in this file is stopped by the DDL — a NOT
     * NULL column with no default raises, and the raise is caught into a clue.
     * `tf_dialogs` is the case the DDL does NOT stop: `peer_type` and
     * `peer_id` are both NOT NULL **with `->default(0)`**, so the bare insert
     * SUCCEEDS and the mirror acquires a dialog keyed on a peer that does not
     * exist. Measured against the real shipped migration before the writer
     * changed: `inserted=1`, one row stored at `peer_type=0, peer_id=0`, and a
     * decomposer clue sitting in the ledger reading "cannot place the fact".
     *
     * The clue is the tell. The decomposer goes out of its way NOT to write
     * 0/0 for a peer — `MirrorFactDecomposer` returns early with "peer not
     * resolvable … instead of silently writing 0/0 into the mirror" — and the
     * column default then does precisely that, one layer down, behind a clue
     * that says the opposite happened. A row the writer cannot address is not
     * a partial row; it is a row that can never be updated, never be found,
     * and never be re-delivered over. So it is skipped, and skipped means
     * NOT COUNTED: `inserted` is the one number a caller ACTS on —
     * `MirrorUpdateIngester::ingest()` emits `UpdateStored` on `inserted > 0` —
     * and inflating it with a row the mirror does not hold would emit an event
     * for a fact that was never stored, which is how a mirror starts lying about
     * what it knows. (Corrected 2026-10-04: this paragraph said "advances a
     * watermark and emit an event". Nothing advances a watermark on `inserted`.)
     *
     * All three consequences are asserted separately, because each can fail
     * alone: the count, the stored row, and the clue.
     */
    public function test_a_dialog_whose_key_columns_carry_a_default_is_skipped_not_stored_under_zeros(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::DIALOGS_MIGRATION)->getConnection();
            $fact = $this->decompose('tf_dialogs', self::PEERLESS_DIALOG);
            $row = $this->ownRow($fact, 'tf_dialogs');

            // Preconditions, asserted rather than assumed: the fixture is only
            // evidence if the decomposer really did refuse to invent the peer
            // AND really did emit the row anyway. Both halves must hold — the
            // decomposer clues and emits, and the writer is the last chance to
            // degrade it, because only the writer knows the LIVE key.
            self::assertContains(
                "tf_dialogs: peer column 'peer' missing from payload — cannot place the fact",
                $fact['clues'],
                'this fixture only proves anything if the peer really is absent',
            );
            self::assertIsArray($row, 'the decomposer must emit the tf_dialogs row');
            self::assertArrayNotHasKey('peer_type', $row, 'the peer type half must be absent, not zero');
            self::assertArrayNotHasKey('peer_id', $row, 'the peer id half must be absent, not zero');

            $write = (new MirrorFactWriter)->write($db, $fact['rows']);

            self::assertSame(
                0,
                $write['inserted'],
                'a skipped row must not be counted as inserted — inserted is what '
                .'MirrorUpdateIngester gates its UpdateStored emit on',
            );
            self::assertSame(
                0,
                $db->table('tf_dialogs')->count(),
                'no row may land for a fact whose declared key cannot be covered',
            );

            // The clue must be a NEW kind of sentence, not the NOT NULL one: an
            // operator reading the ledger has to be able to tell "the DDL's key
            // cannot be filled from this payload" apart from "the payload lacked
            // a required field". So it names the table, the declared key, and
            // exactly which key columns went uncovered.
            self::assertCount(1, $write['fkClues'], 'a skipped row degrades to exactly one per-row clue');
            self::assertSame([], $write['unmappedClues'], 'nothing about the DDL is unmapped here');
            $clue = $write['fkClues'][0];
            self::assertStringContainsString('tf_dialogs', $clue, 'the clue must name the table');
            self::assertStringContainsString('account_id, peer_type, peer_id', $clue, 'the clue must name the declared primary key');
            self::assertStringContainsString('peer_type, peer_id', $clue, 'the clue must name the uncovered key columns');
            self::assertStringNotContainsString('NOT NULL', $clue, 'a defaulted key column never raises NOT NULL — the clue must not borrow that wording');
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The SECOND peerless dialog must degrade exactly like the first.
     *
     * This is the blast radius of the case above, and it is why fabricating the
     * key is worse than losing the row. Once the first peerless dialog has been
     * stored at `(account_id, 0, 0)`, the second one collides on the very
     * primary key that was fabricated for it: SQLite raises
     * `UniqueConstraintViolation: tf_dialogs.account_id, peer_type, peer_id`.
     * `write()` classifies FK, NOT NULL and unknown-column only — a UNIQUE
     * violation is none of those, so it re-raises straight out of the loop and
     * takes the WHOLE ingest transaction with it, along with every row queued
     * behind this one. One missing peer on one dialog is not a per-row problem.
     *
     * The two payloads differ in a real column (`top_message`), so they are two
     * genuinely distinct facts and the fabricated key is the ONLY reason they
     * cannot both be stored. That isolates the property under test.
     */
    public function test_a_second_peerless_dialog_degrades_instead_of_raising_a_unique_violation(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::DIALOGS_MIGRATION)->getConnection();

            $first = $this->ownRow($this->decompose('tf_dialogs', ['_' => 'dialog', 'top_message' => 5, 'unread_count' => 0]), 'tf_dialogs');
            $second = $this->ownRow($this->decompose('tf_dialogs', ['_' => 'dialog', 'top_message' => 6, 'unread_count' => 1]), 'tf_dialogs');

            self::assertIsArray($first);
            self::assertIsArray($second);
            self::assertSame(5, $first['top_message']);
            self::assertSame(6, $second['top_message'], 'two distinct facts — the fabricated key is the only thing in common');

            // The assertion: this call RETURNS. Before the writer changed it
            // raised UniqueConstraintViolation out of the ingest loop.
            $write = (new MirrorFactWriter)->write($db, [
                ['table' => 'tf_dialogs', 'row' => $first],
                ['table' => 'tf_dialogs', 'row' => $second],
            ]);

            self::assertSame(0, $write['inserted'], 'neither peerless row may be counted');
            self::assertSame(0, $db->table('tf_dialogs')->count(), 'neither peerless row may be stored');
            self::assertCount(2, $write['fkClues'], 'each unplaceable row reports its own clue, and neither raises');
            self::assertSame([], $write['unmappedClues']);
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The upsert still converges on re-delivery — the writer's one
     * non-negotiable property, which no guard may cost it.
     *
     * The bus is at-least-once, so the same fact arriving twice MUST land once.
     * A guard that fired on the second delivery would convert an ordinary
     * redelivery into an abort, which is the opposite of what this file wants.
     *
     * `inserted === 2` here is the CONTRACT, not an accident: one row delivered
     * twice is two statements placed and one row held. Do not "correct" this
     * assertion to 1 — `inserted` counts statements, and changing its meaning is
     * an architectural decision with a migration plan, not a bugfix.
     * `tests/Schema/Mirror/InsertedCountsStatementsNotRowsTest.php` pins that.
     */
    public function test_redelivery_of_a_correctly_keyed_fact_still_converges(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::MESSAGES_MIGRATION)->getConnection();
            $row = [
                'table' => 'tf_messages_reply_markup_rows',
                'row' => ['account_id' => 1, 'peer_type' => 1, 'peer_id' => 77, 'id' => 5, 'position' => 0, 'constructor' => 'keyboardButtonRow'],
            ];

            $write = (new MirrorFactWriter)->write($db, [$row, $row]);

            self::assertSame(2, $write['inserted'], 'both deliveries must be processed');
            self::assertSame([], $write['fkClues'], 'a converged redelivery must report no clue');
            self::assertSame(
                1,
                $db->table('tf_messages_reply_markup_rows')->count(),
                'the same fact twice must converge on one row, not abort or duplicate',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * The healthy derived-key tables still place under their derived key, i.e.
     * a row that does NOT carry its PK column under its own name is NORMAL.
     *
     * This is the exact pair the removed guard false-fired on. Both ship
     * `primary(['account_id','id'])` while neither constructor carries `id`;
     * `MirrorFactDecomposer::keyValue()` supplies it from the entry's `keyFrom`
     * derivation. `MirrorDerivedKeyPlacesRowTest` proves the derivation itself,
     * so what is asserted here is only the writer-level consequence: the write
     * path places them and reports NOTHING. That is the contract a per-row PK
     * guard cannot hold without breaking these two tables.
     *
     * ## WHY THE SCHEMA IS TWO MIGRATIONS AND NOT ONE — measured, not assumed
     *
     * This test used to boot `2026_09_14_200041` alone, which was correct until
     * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
     * folded `BotInfo.description` onto the `tf_bot_infos` FACT row and dropped
     * the fabricated `tf_bot_infos_description` table. That migration is part of
     * the shipped dial, so the post-fold `tf_bot_infos` is a DIFFERENT table, and
     * booting the pre-fold half alone hands the writer a schema the package does
     * not ship:
     *
     * ```
     * WITHOUT fold: hasColumn(tf_bot_infos, description) = false   <- what this test booted
     *               tf_bot_infos_description table exists = true    <- the table the fold drops
     * WITH    fold: hasColumn(tf_bot_infos, description) = true
     *               tf_bot_infos_description table exists = false
     *               columns: ["account_id","id","has_preview_medias","description"]
     * ```
     *
     * `mirror-catalog.json` declares `description` in `tf_bot_infos.base`, which
     * is CORRECT and is what makes the writer emit, against that stale schema:
     *
     * ```
     * tf_bot_infos: column 'description' is not in the shipped DDL — field not modelled, skipped
     * ```
     *
     * So the clue was TRUE ABOUT THE SCHEMA IT WAS GIVEN and FALSE ABOUT THE
     * PACKAGE. The product was not defective — the FIXTURE was, by naming one
     * migration of a two-migration change. Naming the migration FILE is what
     * stops that class of drift recurring, so the second file is named here too
     * and no column is transcribed anywhere.
     */
    public function test_a_key_supplied_by_a_derived_path_places_and_reports_nothing(): void
    {
        $previous = Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(
                self::BOTS_MIGRATION,
                self::BOTS_DESCRIPTION_FOLD_MIGRATION,
            )->getConnection();

            $fact = $this->decompose('tf_bot_infos', [
                '_' => 'botInfo',
                'user_id' => 777123,
                'has_preview_medias' => true,
                'description' => 'a bot',
            ]);

            self::assertSame(
                [],
                array_values(array_filter(
                    $fact['clues'],
                    static fn (string $c): bool => str_contains($c, 'keying column'),
                )),
                'a derived key must not raise a keying clue',
            );

            $write = (new MirrorFactWriter)->write($db, $fact['rows']);

            self::assertSame([], $write['fkClues'], 'a derived-key row must place with no write clue');
            self::assertSame([], $write['unmappedClues'], 'a derived-key row must place with no unmapped clue');

            $row = $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->first();
            self::assertNotNull($row, 'the derived-key row must be present');
            self::assertSame(777123, (int) $row->id, 'the key must carry the value the wire supplied');
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * Run the real resolver + decomposer for one table and report the fact.
     *
     * @param  array<string, mixed>  $payload
     * @return array{rows: list<array{table: string, row: array<string, int|string|null>}>, clues: list<string>}
     */
    private function decompose(string $tfName, array $payload): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        return (new MirrorFactDecomposer($resolver, $catalog))->decompose($tfName, self::ACCOUNT, $payload);
    }

    /**
     * The row the decomposer emitted for exactly one table, or null.
     *
     * @param  array{rows: list<array{table: string, row: array<string, int|string|null>}>, clues: list<string>}  $fact
     * @return array<string, int|string|null>|null
     */
    private function ownRow(array $fact, string $tfName): ?array
    {
        foreach ($fact['rows'] as $candidate) {
            if ($candidate['table'] === $tfName) {
                return $candidate['row'];
            }
        }

        return null;
    }
}
