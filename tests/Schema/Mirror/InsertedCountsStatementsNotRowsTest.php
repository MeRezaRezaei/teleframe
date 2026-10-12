<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * THE GATE ON `inserted`: it counts STATEMENTS PLACED, never rows held.
 *
 * ## WHY THIS FILE EXISTS
 *
 * `MirrorFactWriter::write()` returned a count that every reader called
 * `inserted`, and its own docblock claimed the count was "what callers advance a
 * watermark and emit an event on" and that it "means the mirror now holds this
 * fact". Both were false, and the second was load-bearing: it is the invariant
 * the next reader inherits. A docblock that promises a watermark means the next
 * agent advances one, or concludes a row is stored when it is not.
 *
 * Measured 2026-10-04 on real sqlite, one `write()` call, three DISTINCT facts
 * sharing one primary key — a table keyed `primary(['account_id','fact_id'])`,
 * so every entity in one message carries the message's own id as its fact id:
 *
 *     inserted=3   fkClues=[]   unmappedClues=[]   valueClues=[]
 *     rows held: 1   (constructor=messageEntityItalic; bold and textUrl are GONE)
 *
 * Two facts destroyed. Three statements counted. Not one bucket says so, and no
 * exception escaped `write()`. Re-delivering the same batch returns the
 * BYTE-IDENTICAL report — `inserted=3`, still one row — so a converged rewrite
 * is INDISTINGUISHABLE from a fresh insert on every signal this method emits.
 *
 * ## THE COLLIDING TABLE IS HAND-BUILT — NARROWED 2026-10-04
 *
 * This paragraph used to claim the above was measured "on the shipped DDL", and
 * to name `tf_message_entities` as that table. The DDL was executed, but the
 * claim was load-bearing in the wrong direction: it said the COUNTER lesson was
 * a property of ORDINARY TRAFFIC. It never was, and it cannot be. MEASURED
 * 2026-10-04 over ten decoded payload shapes, the shipped `MirrorCatalog` +
 * `MirrorFactDecomposer` emit ZERO rows into `tf_message_entities` — it had 0
 * declared nested sources, so no edge reached it — while emitting 18 into the
 * sibling `tf_messages_entities`, which carries `position` in its key and stores
 * all three entities without loss.
 *
 * `tf_message_entities` was therefore DELETED from `mirror-catalog.json` and from
 * the shipped dial on 2026-10-04 (it was reachable by ctor, which made it look
 * live, and unwritable in practice). `2026_09_29_000100` is in no tag, so
 * editing it in place stranded no install.
 *
 * So the colliding table below is BUILT BY THIS TEST, under the name
 * `tf_probe_three_facts_one_key`, and is labelled as such at every use. The
 * contrast table, `tf_message_actions_users`, IS still shipped DDL and is still
 * executed — `ShippedStoreSchema::capsuleFor()` runs the real migration.
 *
 * WHAT SURVIVES, and why this file still earns its place: the lesson is about
 * `inserted`, not about one table. ANY two decomposed rows sharing a primary key
 * produce exactly this report — the probe is simply the cheapest way to hold
 * three such rows in one table. `tf_messages_entities` is safe today because
 * `position` is IN its key, which is a property of that key and not a guarantee
 * `write()` offers. Narrowed to what is true; NOT deleted, and not weakened:
 * every assertion below is unchanged.
 *
 * `MessageActionPeerScopeBiteTest` records the same measurement on
 * `tf_message_actions` with its narrow key, which is the case that put the lie
 * in the docblock. This file is the general gate rather than that one table's.
 *
 * ## WHAT IS *NOT* ASSERTED HERE, AND WHY IT IS NOT A WEAKNESS
 *
 * This file does not assert that an overwrite is detected. It cannot: no field of
 * `write()`'s return carries the information, and adding one is an architectural
 * change — `placeRow()` would have to learn whether its `upsert()` updated rather
 * than inserted, the signal would have to be carried across every hop that names
 * the four buckets explicitly, and each consumer would need a policy for "this
 * write converged". The first test below therefore asserts the loss is
 * INVISIBLE, so the day somebody adds the signal this gate fails and the change
 * has to be made deliberately, with its consumers re-decided, rather than
 * silently.
 *
 * It also does not assert `inserted` is a row count, because it is not and must
 * not become one. `MirrorUpdateIngester::ingest()` gates its `UpdateStored` emit
 * on `inserted > 0`, and a lower number there would emit fewer events for work
 * that did happen. Changing the meaning is a decision with a migration plan.
 *
 * ## WHY A DOCBLOCK ASSERTION IS PART OF A CODE GATE
 *
 * The first defect was not in the arithmetic; it was a sentence. A test that only
 * pinned the numbers would leave the sentence standing, and the sentence is what
 * the next agent reads. So the third test reads the shipped source and fails if
 * the claim returns — the same shape as the repo's own gate on documentation
 * naming a denied tool.
 *
 * Provenance: `src/Schema/Mirror/MirrorFactWriter.php` (`write()` and
 * `placeRow()`); the CONTRAST table's DDL is
 * `src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php`,
 * executed rather than transcribed, through `ShippedStoreSchema::capsuleFor()`.
 * The COLLIDING table is created by this test — see the narrowing note above.
 */
final class InsertedCountsStatementsNotRowsTest extends TestCase
{
    private const MIG_GAP = __DIR__.'/../../../src/Laravel/Migrations/2026_09_29_000100_create_tf_mirror_gap_tables.php';

    /**
     * The colliding table, BUILT BY THIS TEST — not shipped.
     *
     * It reproduces the shape the retired `tf_message_entities` shipped
     * (`primary(['account_id', <one synthetic id>])`, so siblings of one parent
     * collapse onto one row) under an unmistakable name, because no shipped
     * table carries that shape any more. Deliberately NOT named like a mirror
     * table: a test that builds a table under a production name teaches the
     * next reader that the name exists in production.
     */
    private const PROBE = 'tf_probe_three_facts_one_key';

    private const ACCOUNT = 7;

    /** Every entity in one message carries the message's id as its fact id (see PROBE). */
    private const MESSAGE_ID = 4242;

    private mixed $previousContainer = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousContainer = Container::getInstance();
    }

    protected function tearDown(): void
    {
        ShippedStoreSchema::restoreFacadeApplication($this->previousContainer);
        parent::tearDown();
    }

    /**
     * The measurement, pinned: three facts, three statements, one row, no clue.
     *
     * If a future change makes `inserted` mean rows held, the FIRST assertion
     * fails. That is the intended trip-wire — the change is then a decision to
     * make on purpose, not a bugfix to slip in under a green suite.
     */
    public function test_three_facts_under_one_narrow_key_are_three_statements_and_one_row_and_no_clue(): void
    {
        $db = $this->shippedSchema();

        $write = (new MirrorFactWriter)->write($db, $this->threeFactsOneKey());

        self::assertSame(
            3,
            $write['inserted'],
            'inserted counts STATEMENTS PLACED: one per decomposed row that neither declined '
            .'nor raised. If this ever reads 1, the meaning of the counter has changed and that '
            .'is an architectural decision, not a bugfix.',
        );

        // Every bucket empty. This is the half that makes the defect dangerous:
        // there is no error, no clue and no exception to alert on.
        self::assertSame([], $write['fkClues'], 'a converged overwrite must raise no relationship clue');
        self::assertSame([], $write['unmappedClues'], 'and no unmodelled-field clue');
        self::assertSame([], $write['valueClues'], 'and no value clue');

        self::assertSame(
            1,
            $db->table(self::PROBE)->count(),
            'the probe key is primary(account_id, fact_id) and every entity in one message shares '
            .'the message id, so three facts can only ever be ONE row. This is a KEYING defect and it '
            .'is measured here so the COUNTER defect above it stays visible. The table is built by '
            .'this test: no shipped table carries this shape, which is why it was deleted.',
        );

        // The two lost facts are named, not counted: the surviving row is the LAST
        // one placed, and the first two values are simply gone.
        $held = $db->table(self::PROBE)->first();
        self::assertSame('messageEntityItalic', $held->constructor,
            'convergence keeps the LAST value written and destroys the earlier ones with no signal');
        self::assertSame(8, (int) $held->offset);
    }

    /**
     * A converged rewrite must be indistinguishable from a fresh insert.
     *
     * This is the property that makes the loss unrecoverable in practice: a
     * consumer polling the report cannot tell "three rows landed" from "three
     * rows collapsed onto one and two of them are gone", because the two reports
     * are byte-identical. Any future fix has to change THIS, and this test is what
     * will tell it to.
     */
    public function test_a_converged_rewrite_returns_the_byte_identical_report_to_the_write_that_converged_it(): void
    {
        $db = $this->shippedSchema();
        $writer = new MirrorFactWriter;
        $rows = $this->threeFactsOneKey();

        $first = $writer->write($db, $rows);
        $heldAfterFirst = $db->table(self::PROBE)->count();

        // The bus is at-least-once, so this second delivery is ORDINARY and must
        // converge rather than raise — that is the writer's one non-negotiable.
        $second = $writer->write($db, $rows);
        $heldAfterSecond = $db->table(self::PROBE)->count();

        self::assertSame(
            $first,
            $second,
            'a re-delivery of the same batch returns the same report as the write that collapsed it. '
            .'That is why no consumer can detect the loss: the counter, all four buckets and every '
            .'other field agree between "three rows landed" and "three rows became one".',
        );

        self::assertSame(1, $heldAfterFirst);
        self::assertSame(
            $heldAfterFirst,
            $heldAfterSecond,
            're-delivery must not add rows either — convergence is the point. If this grows, the '
            .'upsert regressed, which is a different defect this file only incidentally witnesses.',
        );
    }

    /**
     * The contrast that makes the counter meaningless as a row count: the SAME
     * number of statements, a DIFFERENT number of rows held, decided by nothing in
     * the report.
     *
     * `tf_message_actions_users` is keyed
     * `primary(['account_id','peer_type','peer_id','message_action_id','position'])`
     * in the same shipped migration, so three rows differing only in `position`
     * are three stored rows — identical `inserted`, opposite outcome. A consumer
     * reading `inserted` alone has no way to tell the two cases apart, which is
     * the whole content of the warning this file exists to keep true.
     */
    public function test_the_same_statement_count_holds_a_different_number_of_rows_and_the_report_cannot_tell(): void
    {
        $db = $this->shippedSchema();

        $colliding = (new MirrorFactWriter)->write($db, $this->threeFactsOneKey());
        $heldAfterColliding = $db->table(self::PROBE)->count();

        $distinct = (new MirrorFactWriter)->write($db, $this->threeSiblingsOneParent());
        $heldAfterDistinct = $db->table('tf_message_actions_users')->count();

        self::assertSame(
            $colliding['inserted'],
            $distinct['inserted'],
            'both writes placed three statements — this is the point: the counter is identical',
        );
        self::assertSame([], $distinct['fkClues'], 'the distinct-key write is as clean as the colliding one');
        self::assertSame(1, $heldAfterColliding, 'three facts, one shipped key: one row held');
        self::assertSame(
            3,
            $heldAfterDistinct,
            'three facts, three shipped keys: three rows held. Same `inserted`, opposite outcome, '
            .'and NOTHING in either report says which happened.',
        );
    }

    /**
     * The sentence is the defect the arithmetic did not have, so it is gated too.
     *
     * `MirrorFactWriter`'s docblock is load-bearing documentation read by every
     * agent that touches the writer. It shipped a claim that was contradicted by
     * the code two hundred lines below it, and a reader who believed it would
     * either advance a watermark on a number that cannot detect the loss it is
     * meant to catch, or conclude a row is stored when it was overwritten. Both
     * failures were prevented here by prose alone, which is why prose is the
     * thing to gate.
     *
     * The phrases below are the exact claims, corrected 2026-10-04. The check is
     * deliberately narrow — it looks for the specific false assertions, not for
     * the words "watermark" or "rows held", which appear legitimately all over
     * this file for other subjects.
     */
    public function test_the_writers_own_documentation_does_not_claim_the_counter_is_a_row_count_again(): void
    {
        $source = file_get_contents($this->writerPath());
        self::assertIsString($source, 'the writer source must be readable — a missing file is not a pass');

        foreach ($this->falseClaims() as $claim) {
            self::assertStringNotContainsString(
                $claim,
                $source,
                'MirrorFactWriter.php carries a claim about `inserted` that measurement refuted: '
                .'"'.$claim.'". It is load-bearing documentation read by the next agent, so it is '
                .'gated rather than trusted. `inserted` counts STATEMENTS PLACED; no caller advances '
                .'a watermark on it; the one caller that acts on it emits `UpdateStored`.',
            );
        }

        // And the positive half: the true contract must be stated, so the gate
        // cannot be satisfied by deleting the explanation instead of correcting it.
        self::assertStringContainsString(
            'STATEMENTS PLACED',
            $source,
            'the corrected contract must be present in the writer, not merely the falsehood absent',
        );
    }

    /**
     * Every false claim, as the exact substring it shipped as.
     *
     * @return list<string>
     */
    private function falseClaims(): array
    {
        return [
            'advances a watermark and emits an event on',
            'advance a watermark',
            'is what callers advance a watermark',
            'inserted` is what callers advance',
            'means the mirror now holds this fact',
            'inserted counts rows the mirror HOLDS',
        ];
    }

    /**
     * @return list<array{table: string, row: array<string, int|string>}>
     */
    private function threeFactsOneKey(): array
    {
        $rows = [];
        foreach ([
            ['messageEntityBold', 0, 2],
            ['messageEntityTextUrl', 3, 4],
            ['messageEntityItalic', 8, 5],
        ] as [$constructor, $offset, $length]) {
            $rows[] = [
                'table' => self::PROBE,
                'row' => [
                    'account_id' => self::ACCOUNT,
                    'fact_id' => self::MESSAGE_ID,
                    'constructor' => $constructor,
                    'offset' => $offset,
                    'length' => $length,
                ],
            ];
        }

        return $rows;
    }

    /**
     * The contrast case: the SAME three statements against a shipped key that
     * DOES distinguish siblings, so all three land.
     *
     * `tf_message_actions_users` carries `position` in its primary key, which is
     * exactly the column the probe lacks — the difference between the
     * two branches of the measurement above, expressed in the shipped DDL rather
     * than in a hand-built schema.
     *
     * @return list<array{table: string, row: array<string, int|string>}>
     */
    private function threeSiblingsOneParent(): array
    {
        $rows = [];
        foreach ([0, 1, 2] as $position) {
            $rows[] = [
                'table' => 'tf_message_actions_users',
                'row' => [
                    'account_id' => self::ACCOUNT,
                    'peer_type' => 2,
                    'peer_id' => 1232026269,
                    'message_action_id' => 5000111,
                    'position' => $position,
                    'value' => 100 + $position,
                ],
            ];
        }

        return $rows;
    }

    private function writerPath(): string
    {
        return __DIR__.'/../../../src/Schema/Mirror/MirrorFactWriter.php';
    }

    /**
     * A real in-memory sqlite: the shipped migration EXECUTED, plus the probe
     * table this test builds.
     *
     * The shipped part is what the CONTRAST case needs and is the only place a
     * real table is used: `tf_message_actions_users` comes from the migration,
     * not from a transcription. The probe is added afterwards and named so that
     * no reader can mistake it for production DDL.
     */
    private function shippedSchema(): Connection
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIG_GAP)->getConnection();

        $schema = $db->getSchemaBuilder();
        $schema->create(self::PROBE, function (Blueprint $table): void {
            $table->unsignedBigInteger('account_id');
            $table->bigInteger('fact_id');
            $table->text('constructor');
            $table->integer('offset')->default(0);
            $table->integer('length')->default(0);

            // The whole point: the parent discriminator is NOT in the key, so
            // three siblings of one parent are one identity.
            $table->primary(['account_id', 'fact_id']);
        });

        return $db;
    }
}
