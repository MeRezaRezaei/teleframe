<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema\Mirror;

use Illuminate\Container\Container;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * The scalar-vector child's value column must EXIST in the shipped DDL.
 *
 * ## THE DIVERGENCE (MEASURED 2026-10-04, against the shipped dial)
 *
 * `MirrorTableResolver::scalarValueColumn()` names the column holding a scalar
 * vector element's value `value` — for EVERY scalar element type (`int`,
 * `long`, `double`, `bool`, `string`, `bytes`; the `match` is at
 * `src/Schema/Mirror/MirrorTableResolver.php:534-545`). The shipped
 * `tf_message_actions_users` agrees and declares `value`. The shipped
 * `tf_saved_star_gifts_collection_id` does NOT: it declares `collection_id`,
 * the WIRE FIELD's name, at
 * `src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php`
 * where the resolver's own `value` column is due.
 *
 * `MirrorFactWriter::write()` pre-filters every row against the live table's
 * column listing (`src/Schema/Mirror/MirrorFactWriter.php:611-619`) and strips
 * a column the DDL does not declare, recording an unmapped clue. So the `value`
 * is removed from the row, and the row is then declined by the empty
 * `collection_id NOT NULL`. Decomposing ONE real `savedStarGift` carrying
 * `collection_id: [11, 22, 33]` against the SHIPPED migration measures:
 *
 *   unmappedClues: 3 — "tf_saved_star_gifts_collection_id: column 'value' is
 *                         not in the shipped DDL — field not modelled, skipped"
 *   fkClues:       3 — "NOT NULL constraint failed (missing required fact data)"
 *   STORED ROWS:   0
 *
 * Every element of every saved gift's `collection_id` list is LOST, and the
 * unmapped clue is the only record that says why. This is the second instance
 * of a defect species this repo has already ruled on: commit `c3bb84f9`
 * (2026-10-04) fixed `tf_todo_items` / `tf_saved_star_gifts` declaring `title` /
 * `message` where the resolver emits `text`, in the same migration, on the same
 * reasoning — "The resolver is RIGHT; these two migrations were the outliers".
 * `collection_id` was missed by that pass and is the last one in this file.
 *
 * ## WHICH SIDE IS WRONG, AND WHY IT IS NOT THE RESOLVER
 *
 * `scalarValueColumn()` is ONE `match` over seven scalar element types with no
 * notion of a wire field name at all, so the resolver cannot name a column
 * after the field without a second, different rule. The sibling scalar-vector
 * table in the shipped dial uses `value`
 * (`2026_09_29_000100_create_tf_mirror_gap_tables.php`, table
 * `tf_message_actions_users`). So the shipped DDL is the side that is out of
 * step, and it is fixed in place — the precedent `c3bb84f9` set in this very
 * file — rather than by a new migration, because a rename migration would move
 * the curated-dial pin for a one-word correction to a hand-authored column and
 * because the resolver's column is what every other layer already agrees with.
 *
 * ## WHY IT SCOPES TO DIRECT CHILDREN, AND WHY THAT IS NOT A WEAKENING
 *
 * The first draft of this test walked the resolved tree to DEPTH 2 and failed
 * on `tf_documents_thumbs_sizes` — a scalar-vector child the resolver builds
 * that the dial does not ship at all. That failure is real information and it
 * changed the claim, so it is recorded here rather than deleted.
 *
 * MEASURED: the resolver synthesizes **16** scalar-vector children at depth 2,
 * none of them in the shipped dial —
 * `tf_admin_log_events_action_{prev,new}_value`, `tf_documents_thumbs_sizes`,
 * `tf_documents_video_thumbs_background_colors`,
 * `tf_phone_calls_protocol_library_versions`, `tf_photos_sizes_sizes`,
 * `tf_photos_video_sizes_background_colors`,
 * `tf_stars_transactions_extended_media_{channels,countries_iso2,winners}`,
 * `tf_sticker_sets_thumbs_sizes`, `tf_themes_settings_message_colors`,
 * `tf_users_color_{colors,dark_colors}`,
 * `tf_users_profile_color_{colors,dark_colors}`. They arise from scalar-vector
 * params INSIDE a union the resolver decomposes as a child
 * (`photoSizeProgressive.sizes:Vector<int>`, `themeSettings.message_colors`,
 * `messageMediaGiveaway.winners`, …).
 *
 * They are unreachable, and that is the whole reason the dial does not ship
 * them: `MirrorFactDecomposer::decomposeChildren()` iterates `$table->children`
 * and never recurses (`src/Schema/Mirror/MirrorFactDecomposer.php:271`), and it
 * has exactly two callers — `decompose()`
 * (`src/Schema/Mirror/MirrorFactDecomposer.php:215`) and
 * `UpdateIngestor::writeChildrenFromCatalog()`
 * (`src/Teleframe/Ingest/UpdateIngestor.php:487`). Both hand it a catalogue root
 * or a nested-source table, so ONE level of children is written and no more.
 * Shipping those 16 tables would add 16 permanently-empty tables to every
 * install, which is a worse outcome than not modelling depth 2 at all.
 *
 * So the assertion is over the children the write path can actually reach, and
 * the divergence it exists to catch is at depth 1. It is still generic: it walks
 * every catalogue root's children and derives the value column the way
 * `childElement()` does, so a THIRD instance anywhere at that depth fails here.
 *
 * ## WHAT THIS TEST IS NOT
 *
 * It does not re-type the DDL: the schema is produced by EXECUTING the shipped
 * migration files (`ShippedStoreSchema::capsuleFor()`), which is the only way
 * a test can see the divergence at all — sqlite is built from those files, so a
 * transcribed second copy would agree with itself forever.
 */
final class ScalarVectorChildColumnMatchesShippedDdlTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../../..';

    private const TL = self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const MIGRATIONS = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    private const ACCOUNT = 7;

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
     * RED until the DDL declares the column the resolver emits.
     *
     * Written GENERICALLY rather than as a one-table assertion, because the
     * failure mode is a species, not an incident: the whole catalogue was
     * audited (262 `base` + `children` declarations, 401 resolved tables) and
     * this is one instance of it. A named assertion would have caught only this
     * table, which is exactly what `c3bb84f9` did and what let a second
     * instance in the same file survive.
     */
    public function test_every_scalar_vector_child_value_column_exists_in_the_shipped_ddl(): void
    {
        $ddl = $this->shippedColumnsByTable();
        $checked = [];

        foreach ($this->scalarVectorChildren() as $child) {
            $columns = $ddl[$child->tfName] ?? null;
            self::assertNotNull(
                $columns,
                "{$child->tfName} is a scalar-vector child the resolver builds, so the dial must ship it",
            );

            foreach ($this->valueCandidates($child) as $candidate) {
                self::assertContains(
                    $candidate,
                    $columns,
                    "{$child->tfName}: the resolver emits a column '{$candidate}' "
                    .'(MirrorTableResolver::scalarValueColumn(), src/Schema/Mirror/MirrorTableResolver.php:534-545) '
                    .'but the shipped DDL does not declare it. MirrorFactWriter::write() strips a column the table '
                    .'does not have (src/Schema/Mirror/MirrorFactWriter.php:611-619), so the element is dropped '
                    .'before the insert and the row is then declined by the empty NOT NULL column — measured: '
                    .'3 unmapped clues, 3 fkClues, 0 stored rows for a three-element collection_id.',
                );
                $checked[] = $child->tfName.'.'.$candidate;
            }
        }

        self::assertNotSame(
            [],
            $checked,
            'the audit must have found at least one scalar-vector child, or it is not looking at anything',
        );
        self::assertContains(
            'tf_saved_star_gifts_collection_id.value',
            $checked,
            'the two measured scalar-vector declarations must both be covered — the ones that were already '
            .'deployed (tf_message_actions_users.value) and the divergent one',
        );
    }

    /**
     * The end-to-end RED: the row must actually LAND, with its real value.
     *
     * Asserting the clue counts alone would be satisfied by a decomposer that
     * emitted nothing, so this reads the stored rows back — the same discipline
     * `ScalarVectorChildElementTest` uses for the `Vector<long>` case, and for
     * the same reason: a row that lands on the column DEFAULT is
     * indistinguishable from a row that landed on the right value unless the
     * value itself is read.
     */
    public function test_a_saved_star_gift_collection_id_lands_in_the_shipped_table_with_its_real_values(): void
    {
        $tl = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $tl);
        $decomposer = new MirrorFactDecomposer(new MirrorTableResolver($catalog, $tl), $catalog);

        // Transcribed from the shipped scheme: `savedStarGift#41df43fc …
        // date:int … gift:StarGift … collection_id:flags.15?Vector<int>`
        // (src/Schema/schema/sources/api_full.tl:2024). `id` is not on the
        // ctor — the catalog's `keyFrom` takes it from the inner StarGift, so
        // the payload must carry one for the parent key to land at all.
        $out = $decomposer->decompose('tf_saved_star_gifts', self::ACCOUNT, [
            '_' => 'savedStarGift',
            'date' => 1712345702,
            'gift' => [
                '_' => 'starGift',
                'id' => 555001,
                'stars' => 25,
                'convert_stars' => 50,
                'upgrade_stars' => 0,
            ],
            'collection_id' => [11, 22, 33],
        ]);

        $db = ShippedStoreSchema::capsuleFor(
            self::MIGRATIONS.'/2026_09_14_200040_create_tf_stars_business_tables.php',
        )->getConnection();

        $write = (new MirrorFactWriter)->write($db, $out['rows']);

        self::assertSame(
            [],
            array_values(array_filter(
                $write['unmappedClues'],
                static fn (string $clue): bool => str_contains($clue, 'tf_saved_star_gifts_collection_id'),
            )),
            'no column may be unmapped: the resolver emits `value` and the shipped DDL must declare it',
        );
        self::assertSame(
            [],
            array_values(array_filter(
                $write['fkClues'],
                static fn (string $clue): bool => str_contains($clue, 'tf_saved_star_gifts_collection_id'),
            )),
            'no FK/NOT-NULL clue: the parent key is covered and the element value is storable',
        );

        $stored = $db->table('tf_saved_star_gifts_collection_id')->orderBy('position')->get();
        self::assertCount(
            3,
            $stored,
            'all three collection slots must reach the shipped child table — measured 0 before the DDL '
            .'declared the column the resolver emits',
        );
        self::assertSame(
            [11, 22, 33],
            array_map('intval', $stored->pluck('value')->all()),
            'the STORED values are the wire values, on the column the resolver named',
        );
    }

    // ── fixtures & helpers ──────────────────────────────────────────────────

    /**
     * Every table the shipped dial actually creates, with its real columns.
     *
     * ALL migration files are executed, not a named subset: a scalar-vector
     * child can live in any of them, and a test that checked two files would
     * miss a third.
     *
     * @return array<string, list<string>>
     */
    private function shippedColumnsByTable(): array
    {
        $files = glob(self::MIGRATIONS.'/*.php');
        self::assertNotSame([], $files, 'the curated dial must not be empty');

        sort($files);

        $capsule = ShippedStoreSchema::capsuleFor(...$files);
        $db = $capsule->getConnection();
        $grammar = $db->getQueryGrammar();

        $out = [];
        foreach ($db->select("select name from sqlite_master where type='table'") as $table) {
            $name = (string) (is_object($table) ? $table->name : $table['name']);
            $out[$name] = array_map(
                static fn ($c): string => (string) (is_object($c) ? $c->name : $c['name']),
                $db->select('pragma table_info('.$grammar->wrapTable($name).')'),
            );
        }

        return $out;
    }

    /**
     * Every positioned DIRECT child whose element is a scalar — i.e. exactly the
     * shape `childElement()` wraps a scalar element under, and exactly the depth
     * `decomposeChildren()` walks (see the class docblock for why depth 2 is out
     * of scope rather than merely forgotten).
     *
     * @return list<MirrorTable>
     */
    private function scalarVectorChildren(): array
    {
        $tl = TlParser::parseFile(self::TL);
        $catalog = MirrorCatalog::load(self::CATALOG, $tl);
        $resolver = new MirrorTableResolver($catalog, $tl);

        $out = [];
        foreach ($catalog->tableNames() as $tfName) {
            try {
                $root = $resolver->resolveAll([$tfName])[0] ?? null;
            } catch (\Throwable $e) {
                continue;
            }
            if ($root === null) {
                continue;
            }
            $stack = $root->children;
            while ($stack !== []) {
                $child = array_shift($stack);
                if ($child->positioned && count($this->valueCandidates($child)) === 1) {
                    $out[$child->tfName] = $child;
                }
            }
        }

        return array_values($out);
    }

    /**
     * The child's columns that are neither the parent key nor the ordinal —
     * the same derivation `MirrorFactDecomposer::scalarChildValueColumn()` uses
     * to decide where a scalar element goes.
     *
     * @return list<string>
     */
    private function valueCandidates(MirrorTable $child): array
    {
        $out = [];
        foreach ($child->columns as $column) {
            if ($column->name === 'account_id' || $column->name === 'position') {
                continue;
            }
            if (in_array($column->name, $child->keyColumns, true)) {
                continue;
            }
            $out[] = $column->name;
        }

        return $out;
    }
}
