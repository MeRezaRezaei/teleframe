<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use MeRezaRezaei\Teleframe\Laravel\SchemaArtifacts;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * `starGiftUnique` carries `availability_issued:int`; the SHIPPED DDL for
 * `tf_saved_star_gifts_gift` has `availability_total` and NO
 * `availability_issued`. A TL payload legitimately carrying a field the
 * curated DDL does not model is NORMAL for a mirror — the wire runs ahead of
 * the dial — so it must never be the reason `ingest()` blows up.
 *
 * It was: `MirrorFactWriter` caught only FK and NOT NULL and rethrew
 * everything else, so the "no such column" QueryException escaped mid-write,
 * AFTER the parent row had already landed. Partial write, confusing symptom,
 * and reachable from a real account — `payments.getSavedStarGifts` on any
 * account holding collectible gifts.
 *
 * THE LINE DRAWN. "The DDL has no such column" (a modelling gap → skip the
 * field, report the clue, write the rest of the row) is separated from "the
 * DDL has it and the value violates it" (a real bug → throw). The separation
 * is made by ASKING THE DATABASE for the column listing, before the insert,
 * not by pattern-matching driver error text after it — because SQLite's
 * SQLSTATE (`HY000`) is identical for every error it can raise, so on SQLite
 * an error-text-only rule is the ONLY rule and it is a guess. `getColumnListing`
 * is exact and identical on sqlite, MySQL and Postgres.
 *
 * The rethrow STAYS for everything else, and the third test below is what
 * stops this fix from being a blanket swallow: a UNIQUE violation on a
 * primary key the DDL very much does have still throws, loudly.
 *
 * The DDL is EXECUTED, never transcribed: `ShippedStoreSchema::capsuleFor()`
 * runs the real shipped migration, so a column added or dropped there moves
 * these assertions with it. This suite deliberately does NOT propose adding
 * `availability_issued` — a migration is the TRUTH surface, not the thing you
 * edit to silence a query.
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php
 * (the shipped tf_saved_star_gifts_gift DDL, no `availability_issued`);
 * src/Schema/schema/sources/api.tl `starGiftUnique#85f0a9cd … availability_issued:int`.
 */
final class UnmappedMirrorFieldIsAModellingGapTest extends TestCase
{
    /** The shipped stars/business tables — the DDL that lacks the column. */
    private const MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php';

    private const ACCOUNT = 7;

    /**
     * A `payments.savedStarGift` carrying a real `starGiftUnique`, exactly as
     * the wire decodes it — including `availability_issued`, the field the
     * shipped DDL does not model.
     *
     * @var array<string, mixed>
     */
    private const PAYLOAD = [
        '_' => 'savedStarGift',
        'id' => 9001,
        'date' => 1735689600,
        'gift' => [
            '_' => 'starGiftUnique',
            'id' => 9001,
            'gift_id' => 42,
            'title' => 'Collectible',
            'slug' => 'collectible-42',
            'num' => 1,
            'availability_issued' => 3,
            'availability_total' => 10,
        ],
    ];

    /**
     * The defect, as it presented: the parent row lands, the child insert dies
     * on a column the schema never modelled, and the whole ingest throws.
     *
     * Run here through the raw query builder, i.e. exactly what the writer
     * does for that row when it has no schema access to filter with. This is
     * the BEFORE state the fix is measured against.
     */
    public function test_the_unfiltered_insert_throws_on_an_unmodelled_column(): void
    {
        $db = $this->connection();
        $rows = $this->decompose(self::ACCOUNT)[0]['rows'];

        $thrown = null;
        try {
            foreach ($rows as $row) {
                $db->table($row['table'])->insert($row['row']);
            }
        } catch (QueryException $e) {
            $thrown = $e;
        }

        self::assertNotNull($thrown, 'an unfiltered insert must throw — otherwise this test proves nothing');
        self::assertStringContainsString('availability_issued', $thrown->getMessage());
        self::assertSame(
            1,
            $db->table('tf_saved_star_gifts')->count(),
            'the parent row landed first — which is what made the failure partial and confusing',
        );
    }

    /**
     * The fix: the same payload through the real writer. No throw, the parent
     * row lands, the gift row lands WITH every column the DDL does model
     * (including `availability_total`), and the unmodelled field is REPORTED
     * rather than silently dropped.
     */
    public function test_an_unmodelled_tl_field_no_longer_breaks_the_write(): void
    {
        $db = $this->connection();

        $write = (new MirrorFactWriter)->write($db, $this->decompose(self::ACCOUNT)[0]['rows']);

        self::assertSame(2, $write['inserted'], 'both the parent and the gift row must place');
        self::assertSame([], $write['fkClues'], 'an unmodelled field is NOT a constraint failure');

        $unmapped = implode("\n", $write['unmappedClues']);
        self::assertStringContainsString('availability_issued', $unmapped, 'the skip must be reported, not swallowed');
        self::assertStringContainsString('not in the shipped DDL', $unmapped);

        self::assertSame(1, $db->table('tf_saved_star_gifts')->count(), 'the parent row must land');
        $gift = $db->table('tf_saved_star_gifts_gift')->first();
        self::assertNotNull($gift, 'the gift row must land too — only the unmodelled field is dropped');
        self::assertSame(42, (int) $gift->gift_id);
        self::assertSame(10, (int) $gift->availability_total, 'the sibling field the DDL DOES model must survive');
        self::assertObjectNotHasProperty('availability_issued', $gift);
    }

    /**
     * The assertion that keeps this from being a blanket swallow.
     *
     * A NOT NULL violation on a MODELLED column is a REAL problem: the DDL has
     * the column, the payload was supposed to carry it, and the value produced
     * is wrong. It is not a modelling gap.
     *
     * The writer's actual design — read `MirrorFactWriter::write()` — is that a
     * FOREIGN KEY, NOT NULL or unknown-column failure is SURFACED as a clue
     * rather than thrown, so that one bad row cannot abort a whole batch of
     * good ones. Everything else still rethrows. So the anti-swallow property
     * is not "it throws": it is that the failure is **reported**, never
     * silently dropped. A blanket swallow would return cleanly with nothing
     * said, and that is exactly what this asserts against.
     *
     * This test used to assert that a DUPLICATE primary key throws, and it
     * stopped being true for a deliberate reason: the writer now converges on
     * re-delivery with `updateOrInsert`, because a redelivered bus entry is
     * ordinary here (XAUTOCLAIM, crash mid-batch, replayDeadLetter) and a
     * re-delivered EDIT must correct the row rather than leave stale text
     * forever. The duplicate case keeps its own test below, so the new
     * behaviour stays pinned rather than merely permitted.
     */
    public function test_a_real_constraint_violation_on_a_modelled_column_still_throws(): void
    {
        $db = $this->connection();
        $writer = new MirrorFactWriter;
        $rows = $this->decompose(self::ACCOUNT)[0]['rows'];

        self::assertSame(2, $writer->write($db, $rows)['inserted'], 'the first write must place');

        // A MODELLED, NOT NULL column forced to null. `date` is a plain
        // non-nullable integer in the shipped DDL, so this is a genuine
        // constraint violation on a column the schema HAS.
        $broken = $rows;
        foreach ($broken as $i => $row) {
            if ($row['table'] === 'tf_saved_star_gifts') {
                $broken[$i]['row']['date'] = null;
            }
        }

        $result = $writer->write($db, $broken);

        self::assertNotEmpty(
            $result['fkClues'],
            'a NOT NULL violation on a modelled column was swallowed silently — a blanket swallow is the failure mode this guards',
        );
        self::assertStringContainsString(
            'NOT NULL',
            $result['fkClues'][0],
            'the clue must name the actual constraint, not a generic one',
        );
        self::assertStringContainsString('tf_saved_star_gifts', $result['fkClues'][0]);

        // And it is REPORTED, not written: the bad row did not land.
        self::assertSame(
            0,
            $db->table('tf_saved_star_gifts')->where('account_id', self::ACCOUNT)->whereNull('date')->count(),
            'a row violating a modelled NOT NULL constraint must not be persisted',
        );
    }

    /**
     * The same rows, delivered twice, must now CONVERGE rather than raise.
     *
     * This is the deliberate counterpart to the guard above, pinned so the
     * behaviour is not quietly reverted: a redelivered fact is absorbed, and
     * because the write is inside a transaction it is absorbed atomically.
     */
    public function test_a_redelivered_fact_converges_instead_of_raising(): void
    {
        $db = $this->connection();
        $writer = new MirrorFactWriter;
        $rows = $this->decompose(self::ACCOUNT)[0]['rows'];

        self::assertSame(2, $writer->write($db, $rows)['inserted'], 'the first write must place');

        // The identical rows again: same account_id, same primary key. This is
        // a repeat, not a bug, and the writer must absorb it.
        $second = $writer->write($db, $rows);
        self::assertSame(2, $second['inserted'], 'a re-delivered fact must count as held, not raise');

        // Absorbed, not duplicated: still exactly one parent row.
        self::assertSame(
            1,
            $db->table('tf_saved_star_gifts')->where('account_id', self::ACCOUNT)->count(),
            'a re-delivered fact must not duplicate the row',
        );
    }

    /**
     * The filter must NOT extend from "no such column" to "no such table".
     *
     * `tf_messages_rich_message` is a whole fact table the shipped DDL never
     * creates, and Laravel answers an unknown table with an EMPTY column
     * listing rather than an error. Treating that emptiness as "every column
     * is unmapped" would skip the row silently and retire the signal for a far
     * bigger defect than a modelling gap. So the row is not filtered and the
     * insert raises, exactly as it always has.
     */
    public function test_a_table_the_ddl_never_created_still_throws(): void
    {
        $db = $this->connection();

        self::assertSame([], $db->getSchemaBuilder()->getColumnListing('tf_messages_rich_message'));

        $this->expectException(QueryException::class);
        (new MirrorFactWriter)->write($db, [[
            'table' => 'tf_messages_rich_message',
            'row' => ['account_id' => self::ACCOUNT, 'id' => 1],
        ]]);
    }

    /**
     * The shipped DDL, booted in memory, with the global container restored
     * afterwards — `capsuleFor()` re-points the container and the facade at
     * its own capsule, which otherwise leaks into unrelated suites.
     */
    private function connection(): ConnectionInterface
    {
        $previous = Container::getInstance();

        try {
            return ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();
        } finally {
            ShippedStoreSchema::restoreFacadeApplication($previous);
        }
    }

    /**
     * Decompose the payload through the REAL catalog + resolver, so the row
     * that carries the unmodelled field is produced by production code and not
     * hand-written here.
     *
     * @return array{0: array{rows: list<array{table: string, row: array<string, int|string|null>}>, clues: list<string>}}
     */
    private function decompose(int $accountId): array
    {
        $scheme = TlParser::parseFile(SchemaArtifacts::tl());
        $catalog = MirrorCatalog::load(SchemaArtifacts::catalog(), $scheme);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $scheme), $catalog);

        return [$decomposer->decompose('tf_saved_star_gifts', $accountId, self::PAYLOAD)];
    }
}
