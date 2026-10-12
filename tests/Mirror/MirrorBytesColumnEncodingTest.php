<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * A TL `bytes` value must be ENCODED before it lands in the shipped TEXT
 * column that holds it. The schema always knew this; only the writer-side
 * implementation was missing.
 *
 * ## The live failure this pins
 *
 * Discovery routed into the mirror persisted part of a real
 * `messages.getDialogs` page and then aborted the whole transaction:
 *
 *     SQLSTATE[HY000]: General error: 1366 Incorrect string value:
 *     '\xCE\xC8\xF2\xB1\xC7\xE5...' for column 'stripped_thumb' at row 1
 *
 * `tf_chats_photo.stripped_thumb` ships as `$table->text(...)`
 * (`src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php`), and
 * MySQL in strict mode refuses raw binary in a TEXT column. Because the mirror
 * write is wrapped in `$db->transaction(...)`
 * (`MirrorIngesterFactory::buildResultRoute()`), one photo on the page rolled
 * back every other row on it — which is exactly the observed shape: `tf_users`
 * landed, `tf_chats` stayed at 0.
 *
 * ## Why the ASSERTION is on the value, not on the failure
 *
 * sqlite is lenient where MySQL is not: it will happily store raw bytes in a
 * TEXT column. A test that waits for a `QueryException` would therefore pass on
 * sqlite forever while production still dies. Every test here asserts on WHAT
 * LANDS — that the stored value is the encoded form and that it decodes back to
 * the original bytes — so the proof travels with the value, not with a driver
 * quirk.
 *
 * ## Why HEX and not base64 — the encoding is not a free choice
 *
 * Three independent artefacts of THIS repo already decide it, and they agree:
 *
 *  1. NF5 §8 (`docs/superpowers/specs/2026-09-11-telegram-mirror-schema-nf5-design.md`)
 *     is explicit for the 79 bytes fields in the mirror: *"stored hex-encoded
 *     as plain VARCHAR … the MTProto serialization layer calls `hex2bin()` …
 *     No binary column, no base64 bloat — hex is exactly 2× the bytes,
 *     reversible with one stdlib call."*
 *  2. `MirrorTable::$hexColumns` and `MirrorColumn::$hex` are already NAMED for
 *     hex, and `MirrorTableResolver::isBytesBaseField()` carries that naming in
 *     its own docblock: *"stored as hex-encoded VARCHAR/TEXT (NF5 §8) — the
 *     column must carry the hex marker so ingest encodes"*. That sentence
 *     names this file as the missing half.
 *  3. The base64 "precedent" in `src/Schema/Generated/Data/Types/*.php`
 *     (*"bytes params carried as base64 strings: stripped_thumb"*) is a
 *     DOCBLOCK ANNOTATION on generated DTOs. Nothing in the codebase ever calls
 *     `base64_encode()` on a mirror value — the only real `base64_encode()`
 *     call sites are `SessionData` (wire transport), `TelegramVault` (sealed
 *     blobs) and `CallbackData` (a callback payload). So base64 is not a
 *     persistence convention here; it is a note on a different layer.
 *
 * Choosing base64 would therefore have meant the mirror storing one alphabet
 * while its own spec, its own column names and its own resolver docblock all
 * say hex, and the `hexColumns` list would still have been a lie.
 *
 * ## What it means for data already stored
 *
 * Any row written before this change that reached the database held either raw
 * binary (MySQL strict → the row was never written; the transaction rolled
 * back, so there is nothing to migrate) or, on a lenient driver, raw binary in
 * a TEXT column. Decoding is unambiguous in one direction only — `hex2bin()` on
 * an already-hex value is a corruption — so there is no safe blanket
 * back-fill. The correct repair for pre-existing raw-binary rows is to re-ingest
 * them from Telegram, which is idempotent by primary key. That is a deliberate
 * choice: the mirror is a projection of remote state, so re-deriving it is both
 * cheaper and more trustworthy than guessing at a migration.
 */
final class MirrorBytesColumnEncodingTest extends TestCase
{
    /** Chats dial — ships `tf_chats_photo.stripped_thumb` as TEXT. */
    private const CHATS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200002_create_tf_chats_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    private const ACCOUNT = 7;

    /**
     * Raw bytes off the wire, of the shape that killed the live run: a
     * `flags.1?bytes stripped_thumb` JPEG thumbnail. Deliberately NOT valid
     * UTF-8 — that is the whole point, and it is also why a broken probe here
     * prints as an empty string (`json_encode()` refuses invalid UTF-8 and
     * returns `false`).
     */
    private const THUMB = "\xF0\x9F\x92\xB7\xCE\xC8\xF2\xB1\xC7\xE5\x00\xFF\x10";

    /** A `chat` carrying a `chatPhoto`, exactly as the wire decodes it. */
    private function chatPayload(?string $thumb): array
    {
        return [
            '_' => 'chat',
            'id' => -1001234,
            'title' => 'A chat with a photo',
            'photo' => [
                '_' => 'chatPhoto',
                'flags' => 3,
                'has_video' => true,
                'photo_id' => 555,
                'stripped_thumb' => $thumb,
                'dc_id' => 2,
            ],
        ];
    }

    /**
     * THE PROPERTY: a bytes column holds the ENCODED form, and it decodes back
     * to the exact bytes the wire carried.
     *
     * Both halves are asserted. "The value is a string" would pass today and
     * prove nothing; `hex2bin($stored) === $original` is the round trip that
     * makes the encoding reversible, which is the property the whole design
     * rests on (NF5 §8: "re-encoded with `hex2bin()` at the MTProto call
     * site").
     */
    public function test_a_bytes_column_lands_encoded_and_round_trips_to_the_original_bytes(): void
    {
        $previous = \Illuminate\Container\Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::CHATS_MIGRATION)->getConnection();
            $fact = $this->decompose('tf_chats', $this->chatPayload(self::THUMB));

            // Precondition, asserted rather than assumed: the resolver really
            // does mark this column as a bytes/hex column. If it stopped doing
            // so, this file would go quietly vacuous.
            self::assertContains(
                'stripped_thumb',
                $this->hexColumnsOf('tf_chats', 'tf_chats_photo'),
                'the resolver must still mark tf_chats_photo.stripped_thumb as a bytes column',
            );

            $row = $this->ownRow($fact, 'tf_chats_photo');
            self::assertIsArray($row, 'the decomposer must emit the tf_chats_photo row');

            // RED before the fix, GREEN after: the emitted value is the hex
            // encoding, NOT the raw binary.
            self::assertSame(bin2hex(self::THUMB), $row['stripped_thumb'], 'the emitted value must be hex-encoded, not raw binary');

            $write = (new MirrorFactWriter)->write($db, $fact['rows']);
            self::assertSame([], $write['fkClues'], 'an encoded bytes value must not produce a write clue');

            $stored = $db->table('tf_chats_photo')->value('stripped_thumb');
            self::assertIsString($stored, 'the row must actually be stored');
            self::assertSame(bin2hex(self::THUMB), $stored, 'what lands in the TEXT column must be the hex form');
            self::assertNotSame(self::THUMB, $stored, 'raw binary in a TEXT column is the defect this file exists to prevent');

            // The round trip — the property that makes the choice reversible.
            self::assertSame(
                self::THUMB,
                hex2bin($stored),
                'hex2bin() of the stored value must reproduce the original bytes exactly (NF5 §8 decode boundary)',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * A NON-bytes text column on the SAME row is left completely alone.
     *
     * Encoding is keyed on the resolver's per-column bytes marker, never on the
     * column's SQL type or on "does this look like binary". If that key were
     * wrong in the other direction, every string in the mirror — including
     * titles and usernames, which are ordinary text and are read back by every
     * consumer — would be hex-mangled. This is the guard against that.
     */
    public function test_a_non_bytes_text_column_on_the_same_row_is_not_encoded(): void
    {
        $previous = \Illuminate\Container\Container::getInstance();

        try {
            $db = ShippedStoreSchema::capsuleFor(self::CHATS_MIGRATION)->getConnection();
            $fact = $this->decompose('tf_chats', $this->chatPayload(self::THUMB));

            $row = $this->ownRow($fact, 'tf_chats');
            self::assertIsArray($row, 'the decomposer must emit the tf_chats row');
            self::assertSame('A chat with a photo', $row['title'], 'a plain text column must pass through verbatim');
            self::assertNotSame(bin2hex('A chat with a photo'), $row['title'], 'a text column must never be hex-encoded');

            $write = (new MirrorFactWriter)->write($db, $fact['rows']);
            self::assertSame([], $write['fkClues']);

            self::assertSame(
                'A chat with a photo',
                $db->table('tf_chats')->value('title'),
                'the stored text must be the text the wire carried',
            );
            self::assertSame(
                'chatPhoto',
                $db->table('tf_chats_photo')->value('constructor'),
                'the discriminator — another plain string column — must also pass through unencoded',
            );
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * THE DECISION, pinned: the decomposer encodes a bytes value EXACTLY ONCE
     * and never guesses that a value "looks already encoded".
     *
     * ## What was decided
     *
     * A bytes value whose byte content happens to be a printable ASCII run is
     * encoded like every other bytes value. It is NOT skipped on the theory
     * that it has been encoded already.
     *
     * ## Why — the alternative is silently unsafe in BOTH directions
     *
     * The tempting guard is "if it already looks like base64 (or hex), leave
     * it". It cannot be written safely:
     *
     *  - **Detecting is ambiguous, because the alphabets are subsets of
     *    arbitrary binary.** The bytes `41 42` are the ASCII string `"AB"`,
     *    which is valid hex. The bytes `2f 39 41` are `"/9A"`, which is valid
     *    base64. A heuristic guard would leave THAT raw binary in the column —
     *    reinstating the exact MySQL 1366 this file exists to fix, and doing it
     *    only for the payloads that happen to look encoded, which is the worst
     *    possible failure mode: data-dependent, rare, and invisible.
     *  - **Skipping is not lossless; encoding always is.** Encoding an
     *    already-encoded value produces `hex(hex(x))`, which is ugly but
     *    perfectly decodable and never ambiguous. Skipping a value that was
     *    NOT encoded destroys it.
     *
     * So the rule is unconditional and the boundary is singular: this class is
     * the ONE place a bytes value is encoded, and `hex2bin()` is applied once
     * at the MTProto call site (NF5 §8). Nothing else in the pipeline re-encodes.
     *
     * The generated DTOs' base64 note (`src/Schema/Generated/Data/Types/ChatPhotoData.php`)
     * does not reach this path: those are separate DTOs, the mirror decomposer
     * reads raw decoded TL, and nothing in the codebase ever `base64_encode()`s
     * a mirror value. Should a future caller ever route a DTO-carried value
     * through here, encoding it is still the CORRECT answer — the DTO's own
     * bytes are already base64 text, and `hex2bin` at the call site returns
     * them exactly.
     */
    public function test_a_bytes_value_that_looks_already_encoded_is_still_encoded_exactly_once(): void
    {
        $previous = \Illuminate\Container\Container::getInstance();

        try {
            // Three values that a naive "already encoded?" heuristic would
            // mis-handle in one way or another:
            //   - "AB"          : ASCII text that is ALSO valid hex
            //   - "/9A"         : ASCII text that is ALSO valid base64
            //   - "deadbeef"    : hex-shaped, which is what the pre-existing
            //                     tests hand-build for this column
            foreach (['AB', '/9A', 'deadbeef'] as $looksEncoded) {
                $db = ShippedStoreSchema::capsuleFor(self::CHATS_MIGRATION)->getConnection();
                $fact = $this->decompose('tf_chats', $this->chatPayload($looksEncoded));

                $row = $this->ownRow($fact, 'tf_chats_photo');
                self::assertIsArray($row, "the decomposer must emit the photo row for '{$looksEncoded}'");

                self::assertSame(
                    bin2hex($looksEncoded),
                    $row['stripped_thumb'],
                    "a bytes value whose bytes read as '{$looksEncoded}' must still be encoded exactly once",
                );
                self::assertNotSame(
                    $looksEncoded,
                    $row['stripped_thumb'],
                    "the decomposer must never pass '{$looksEncoded}' through unencoded on a guess",
                );

                (new MirrorFactWriter)->write($db, $fact['rows']);

                $stored = $db->table('tf_chats_photo')->value('stripped_thumb');
                self::assertSame(
                    $looksEncoded,
                    hex2bin((string) $stored),
                    "one encode, one decode, original bytes back for '{$looksEncoded}'",
                );
            }
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

        return (new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog))
            ->decompose($tfName, self::ACCOUNT, $payload);
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

    /** @return list<string> the resolved table's declared bytes/hex columns */
    private function hexColumnsOf(string $parentTf, string $childTf): array
    {
        $scheme = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $parent = (new MirrorTableResolver($catalog, $scheme))->resolveAll([$parentTf])[0];

        foreach ($parent->children as $child) {
            if ($child->tfName === $childTf) {
                return $child->hexColumns;
            }
        }

        return [];
    }
}
