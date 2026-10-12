<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Ingest\MirrorResultIngester;
use MeRezaRezaei\Teleframe\Schema\Generator\Model\TlScheme;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorNestedSourceWalker;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * THE GATE A CATALOG `scoped_by` EDIT MUST NOT BREAK: a `payments.starsStatus`
 * write leaves NO modelling gap against the shipped stars DDL.
 *
 * ## THE REGRESSION THIS PINS (Task 2.1, round 1)
 *
 * Task 2.1 added `"scoped_by": ["peer_type","peer_id"]` to `tf_stars_subscriptions`
 * and reported it as landed. It was a REGRESSION, and the reasoning behind it was
 * self-contradictory: the identical argument — "a `scoped_by` on a child-bearing
 * table widens its children's PRIMARY KEYs past the shipped DDL, and migrations
 * are Task 2.2's files" — was correctly applied to `tf_messages` and withheld
 * there, then applied anyway to `tf_stars_subscriptions`.
 *
 * `makeChildPkCols()` copies a parent's key columns onto every child, so the
 * declaration widened the PK of all FIVE tables resolved beneath it at once
 * (`_pricing`, `_chat_invite_hash`, `_title`, `_photo`, `_invoice_slug`). Only
 * four of those come from the entry's `children` list; `_pricing` comes from the
 * BASE field `pricing`, whose `FK→` shape `resolveTable()` decomposes into a
 * table exactly as it does for a `children` entry — so the blast radius is five
 * tables, and each named a key column its table does not have. The parent still
 * placed — its own DDL has `peer_type`/`peer_id` columns, indexed — while every
 * one of the five emitted, per table:
 *
 *   tf_stars_subscriptions_pricing: column 'peer_type' is not in the shipped DDL
 *   — field not modelled, skipped
 *
 * ## WHY THAT IS A RUN FAILURE AND NOT A NOISE LINE
 *
 * Task 1.2 made `unmappedClues` fail the run: `teleframe:ingest:gate` treats a
 * modelling-gap clue alone as "schema integrity not reached"
 * (`IngestGateCommand.php:208`, `:218-223`), and `BackfillCommand` reports the
 * same bucket. So the edit broke ingest and backfill for EVERY account carrying
 * a `payments.starsStatus`, while the tree stayed green on every assertion that
 * did not run the writer. The same probe against `3ed4dc5d` yields
 * `UNMAPPED: []` — newly introduced by the catalog edit, not pre-existing.
 *
 * ## WHAT IS ASSERTED
 *
 * The REAL shipped catalog, the REAL decomposer + writer triple
 * (`MirrorResultIngester` — the same collaborators, in the same order, as
 * `MirrorIngesterFactory::buildResultRoute()`), and the REAL shipped migration
 * executed by `ShippedStoreSchema::capsuleFor()`. Nothing about the DDL is
 * transcribed here, so a column added or dropped in the stars dial moves this
 * assertion with it.
 *
 * The second test is the RED PROOF as a standing control, not a recollection: it
 * re-adds the exact `scoped_by` edit to the shipped JSON, byte-derived by
 * DELETING nothing and ADDING one key, runs the SAME payload against the SAME
 * shipped schema, and asserts the modelling-gap clues DO appear. If the gate
 * below ever stops protecting the write path, that control fails here — so the
 * protection cannot rot into an assertion that only passes because nothing
 * writes.
 *
 * Provenance: src/Schema/schema/mirror-catalog.json (`tf_stars_subscriptions`,
 * base[1] = the curated peer pair, FOUR `children`; MEASURED 2026-10-03 as FIVE
 * tables resolved beneath it, the fifth coming from the base field `pricing`);
 * schema/sources/api.tl:1968,1970 (`starsSubscriptionPricing#5416d58 period:int
 * amount:long`, `starsSubscription#2e6eab1a ... id:string peer:Peer
 * until_date:int pricing:StarsSubscriptionPricing chat_invite_hash:flags.3?string
 * title:flags.4?string photo:flags.5?WebDocument invoice_slug:flags.6?string`);
 * src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php
 * (children keyed `primary(['account_id','id'])`);
 * src/Schema/Mirror/MirrorFactWriter.php:546 (the clue string asserted below);
 * src/Laravel/Console/IngestGateCommand.php:208,218-223 (why a clue fails a run).
 */
final class StarSubscriptionKeyingLeavesNoModellingGapTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    /** The shipped stars domain — the parent, its base-field fact and four `children`. */
    private const MIGRATION = self::PACKAGE_ROOT.'/src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php';

    private const ACCOUNT = 11;

    private const SUBSCRIPTION_ID = 'sub-alpha';

    /**
     * THE BLAST RADIUS — every table `MirrorTableResolver` resolves BENEATH
     * `tf_stars_subscriptions`, DERIVED from the shipped catalog at test time
     * rather than transcribed here, so a catalog change moves every assertion
     * here with it instead of leaving a stale list behind.
     *
     * MEASURED 2026-10-03 (Task 2.1 round 2) against the shipped entry, which
     * declares four `children` (`chat_invite_hash`, `title`, `photo`,
     * `invoice_slug`) and a BASE field `pricing` shaped `FK→StarsSubscriptionPricing`:
     * FIVE tables come out.
     *
     * The fifth is the point. `resolveTable()` turns a base field into a child
     * table on its SHAPE — `isFactShape()` accepts `FK→` and `1:N` — exactly as
     * it does for a `children` entry, so `pricing` is a base field in the JSON
     * and `tf_stars_subscriptions_pricing` in the resolved tree. Calling the
     * list "the four child tables" therefore both undercounted the blast radius
     * and mislabelled one member of it.
     *
     * Each of the five names a key column its shipped `primary()` lacks, so each
     * emits BOTH clues (`peer_type` and `peer_id`): TEN, two per table. Measured
     * with the reverted `scoped_by` re-added — see
     * `test_the_reverted_catalog_edit_is_what_would_break_the_write`, which
     * asserts all ten, per table, from this same derived set.
     *
     * @return list<string>
     */
    private function blastRadiusTables(): array
    {
        $catalog = MirrorCatalog::load(self::CATALOG, $this->scheme());
        $parent = (new MirrorTableResolver($catalog, $this->scheme()))
            ->resolveAll(['tf_stars_subscriptions'])[0];

        $tables = array_values(array_map(
            static fn (MirrorTable $child): string => $child->tfName,
            $parent->children,
        ));
        sort($tables);

        self::assertNotSame(
            [],
            $tables,
            'the stars entry resolved to NO child tables — the blast radius below would be vacuous, '
            .'so a derivation change must not pass as a green control',
        );

        return $tables;
    }

    /**
     * A real `payments.starsStatus` result, transcribed from the TL scheme:
     * `payments.starsStatus#7f094d0d balance:StarsAmount
     * subscriptions:flags.1?Vector<StarsSubscription>
     * history:flags.3?Vector<StarsTransaction> = payments.StarsStatus`.
     *
     * Every field-gated subscription child present in the payload is carried
     * (`pricing` is REQUIRED; `chat_invite_hash` and `title` are
     * `flags.3`/`flags.4`), because a row that drops for a MISSING NOT NULL
     * value would confound this test with `fkClues` and prove nothing about
     * keying. `photo` is a `WebDocument`, supplied as `webDocument` with every
     * NOT NULL column the shipped child table declares.
     *
     * @var array<string, mixed>
     */
    private const PAYLOAD = [
        '_' => 'payments.starsStatus',
        'flags' => 0b000001, // subscriptions:flags.1
        'balance' => ['_' => 'starsAmount', 'amount' => 900, 'nstars' => 0],
        'subscriptions' => [
            [
                '_' => 'starsSubscription',
                'flags' => 0b1111000, // chat_invite_hash:3 | title:4 | photo:5 | invoice_slug:6
                'id' => self::SUBSCRIPTION_ID,
                'peer' => ['_' => 'peerUser', 'user_id' => 9002],
                'until_date' => 1712432078,
                'pricing' => ['_' => 'starsSubscriptionPricing', 'period' => 30, 'amount' => 100],
                'chat_invite_hash' => 'INV-HASH',
                'title' => 'a subscription',
                'invoice_slug' => 'a-slug',
                'photo' => [
                    '_' => 'webDocument',
                    'id' => 5150,
                    'access_hash' => 9001,
                    'url' => 'https://t.me/photo.jpg',
                    'size' => 4096,
                    'mime_type' => 'image/jpeg',
                ],
            ],
        ],
        'history' => [],
    ];

    /**
     * THE GATE. No modelling gap, no matter how the parent is keyed, and the
     * parent plus every table beneath it place with real values.
     */
    public function test_a_stars_status_write_leaves_no_unmapped_column(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();

        $written = $this->route(MirrorCatalog::load(self::CATALOG, $this->scheme()))
            ->ingest($db, self::ACCOUNT, self::PAYLOAD);

        self::assertSame(
            [],
            $written['unmappedClues'],
            'a column the shipped DDL does not have is a modelling gap, and Task 1.2 makes one fail '
            .'teleframe:ingest:gate — so this write must name no column the stars migrations lack',
        );

        self::assertSame(
            [],
            $this->fkCluesFor($written['fkClues'], 'tf_stars_subscriptions'),
            'the parent and every table beneath it must decompose whole; an fkClue here means a NOT NULL '
            .'the payload did not satisfy and this test is measuring the wrong thing',
        );

        // The VALUE, not a row count: a count assertion would pass against a
        // table holding zero rows. The parent row is what every table beneath it
        // keys on, so it must carry the peer scope the wire sent.
        $parent = $db->table('tf_stars_subscriptions')->where('account_id', self::ACCOUNT)->first();

        self::assertNotNull($parent, 'tf_stars_subscriptions placed no row for the mirrored subscription');
        self::assertSame(self::SUBSCRIPTION_ID, (string) $parent->id, 'the parent must key on the TL string id');
        self::assertSame(9002, (int) $parent->peer_id, 'the nested StarsSubscription.peer must reach the peer_id column');
        self::assertSame(1, (int) $parent->peer_type, 'peer_type 1 = peerUser, from the real nested Peer');

        foreach ($this->blastRadiusTables() as $child) {
            self::assertSame(
                1,
                (int) $db->table($child)->where('account_id', self::ACCOUNT)->count(),
                "{$child} placed no row — it is keyed on the parent, so an empty child means the "
                .'subscription did not decompose whole',
            );
        }

        self::assertSame(
            30,
            (int) $db->table('tf_stars_subscriptions_pricing')->where('account_id', self::ACCOUNT)->value('period'),
            'the pricing child must carry the real StarsSubscriptionPricing.period',
        );
    }

    /**
     * THE RED PROOF, as a standing control.
     *
     * The SAME payload, the SAME shipped schema, the SAME writer — against the
     * shipped catalog with `"scoped_by": ["peer_type","peer_id"]` added back to
     * `tf_stars_subscriptions`. Every table beneath it must then report the
     * modelling gap, which is what makes the assertion above a real gate rather
     * than a statement that nothing writes.
     *
     * MEASURED 2026-10-03 (Task 2.1 round 2): TEN clues over FIVE tables, two
     * per table. The loop below walks the DERIVED blast radius, so it covers
     * `invoice_slug` — which the previous hand-written four-field loop omitted,
     * and which emits both clues like the rest. An under-asserting control whose
     * own docblock overstated its count is worse than no control: it looks like
     * the boundary was measured.
     */
    public function test_the_reverted_catalog_edit_is_what_would_break_the_write(): void
    {
        $db = ShippedStoreSchema::capsuleFor(self::MIGRATION)->getConnection();

        $written = $this->route($this->catalogWithScopeDeclaredOn())
            ->ingest($db, self::ACCOUNT, self::PAYLOAD);

        self::assertNotSame(
            [],
            $written['unmappedClues'],
            'the control no longer reproduces the regression — either the writer stopped reporting an '
            .'unmodelled column (the gate above is then vacuous) or the catalog edit no longer widens '
            .'the children PKs (the reason for the revert is stale and must be re-measured)',
        );

        // Asserted per table, because "some clue appeared" is not the finding: the
        // blast radius is that the PARENT still places and only the tables below
        // it degrade, which is why a parent-only assertion missed this entirely.
        $blast = $this->blastRadiusTables();
        foreach ($blast as $table) {
            self::assertNotSame(
                [],
                $this->fkCluesFor($written['unmappedClues'], $table),
                "{$table} must report the gap when the parent's key is widened — "
                .'its shipped primary() is (account_id, id) and carries neither peer_type nor peer_id',
            );
        }

        // Both halves of the widened key, on every table of the blast radius: the
        // per-table assertion above proves each table is named by at least one
        // clue, and this proves no table is named by only one of the two columns.
        self::assertSame(
            2 * count($blast),
            count($written['unmappedClues']),
            'the control must see BOTH unmodelled columns on EVERY table of the blast radius — '
            .'one clue per table is a half-measurement of the same regression',
        );

        self::assertSame(
            [],
            $this->fkCluesFor($written['unmappedClues'], 'tf_stars_subscriptions'),
            'the PARENT places either way: its own DDL has peer_type/peer_id columns, so a '
            .'parent-scoped assertion would pass on the broken catalog',
        );
    }

    /**
     * Clues mentioning one table (not a table with that table as a prefix), so a
     * child's clue cannot satisfy its parent's assertion.
     *
     * @param  list<string>  $clues
     * @return list<string>
     */
    private function fkCluesFor(array $clues, string $table): array
    {
        return array_values(array_filter(
            $clues,
            static fn (string $clue): bool => str_starts_with($clue, $table.':'),
        ));
    }

    /**
     * The shipped catalog with ONE key added: `tf_stars_subscriptions` declares
     * the enclosing scope, exactly as Task 2.1 first landed it.
     *
     * Derived by adding a key to the real file rather than by transcribing it,
     * so the two arms differ by precisely the edit under test and nothing else.
     * `JSON_UNESCAPED_UNICODE` is deliberately not used: the artifact escapes its
     * `FK→` arrows and the round-trip must not unescape them.
     */
    private function catalogWithScopeDeclaredOn(): MirrorCatalog
    {
        $raw = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        $raw['tf_stars_subscriptions']['scoped_by'] = ['peer_type', 'peer_id'];

        $path = sys_get_temp_dir().'/teleframe-stars-scope-declared.json';
        file_put_contents($path, json_encode($raw, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, $this->scheme());
        } finally {
            @unlink($path);
        }
    }

    /**
     * The real ingest route: the same walker, decomposer and writer triple
     * `MirrorIngesterFactory::buildResultRoute()` calls, in the same order.
     */
    private function route(MirrorCatalog $catalog): MirrorResultIngester
    {
        $scheme = $this->scheme();
        $resolver = new MirrorTableResolver($catalog, $scheme);

        return new MirrorResultIngester(
            new MirrorNestedSourceWalker($catalog),
            new MirrorFactDecomposer($resolver, $catalog),
            new MirrorFactWriter,
        );
    }

    private function scheme(): TlScheme
    {
        return TlParser::parseFile(self::TL);
    }
}
