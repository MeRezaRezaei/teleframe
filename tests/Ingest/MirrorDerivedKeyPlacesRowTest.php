<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Ingest;

use Illuminate\Database\ConnectionInterface;
use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactDecomposer;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorFactWriter;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use MeRezaRezaei\Teleframe\Tests\Store\ShippedStoreSchema;
use PHPUnit\Framework\TestCase;

/**
 * Two shipped mirror tables declared a PRIMARY KEY on a column their TL
 * constructor never carries, so every row destined for them was silently
 * dropped by the write path.
 *
 *   tf_bot_infos        botInfo       (TL 697)      — no `id`; the ctor's own `user_id` IS the identity
 *   tf_saved_star_gifts savedStarGift (TL 2024)     — no `id`; the identity is the NESTED StarGift.id
 *
 * Both ship `primary(['account_id','id'])`, so the DDL was never the defect —
 * the resolver's inability to FILL `id` was. The fix is an expressed key
 * derivation: the catalog entry declares `"key": ["id"]` (the column the DDL
 * keys on) plus `"keyFrom": {"id": "user_id"}` / `{"id": "gift.id"}` (where the
 * value genuinely comes from). No column is invented and none is renamed to a
 * value the wire does not have.
 *
 * Why this suite EXECUTES the shipped migrations: a test that transcribes the
 * DDL is a second copy of it and drifts — the disease this project has paid for
 * repeatedly (see `ShippedStoreSchema`'s own docblock). So the tables here are
 * built by running the real migration files, and the assertions are about ROWS
 * THAT EXIST afterwards, never about what the SQL looks like.
 *
 * Every test here runs the write path TWICE against the same shipped schema:
 * once with the pre-fix catalog (the real catalog JSON with the `key`/`keyFrom`
 * fields stripped — the byte-derived "before", not a hand-copy) and once with
 * the shipped catalog. The first run must FAIL LOUDLY and place nothing; the
 * second must LAND the row. A test that only proves the happy path proves
 * nothing about the defect.
 *
 * WHAT THE "FIRST RUN MUST FAIL LOUDLY" MEANS, AND HOW IT MOVED ON
 * 2026-10-11. Both shapes are asserted below for the two entries, and they are
 * NOT the same defect.
 *
 * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
 * folded `BotInfo.description` from a fabricated child table onto the
 * `tf_bot_infos` fact row, and the catalog followed it: `tf_bot_infos.base` is
 * no longer empty, it is `[["description","TEXT NOT NULL"]]`. That moved this
 * file's `tf_bot_infos` case OFF the resolver's rule 5 (empty base -> THROW) and
 * ONTO rule 4 (non-empty base, `base[0]` not self-identifying -> fabricate
 * `{singular}_id`). Both are now asserted where each genuinely lives:
 *
 *   tf_bot_infos        rule 4 -> the WRITE PATH refuses the fabricated key:
 *                       "row skipped, not stored under a fabricated key".
 *                       Per row, so it degrades instead of aborting the batch.
 *   tf_saved_star_gifts rule 4 -> unchanged; the clue names the invented
 *                       `saved_star_gift_id` and nothing is placed.
 *
 * Rule 5's THROW remains armed and is pinned by the synthetic empty-base probe
 * in `tests/Schema/MirrorScopedKeyDeclarationTest`; no shipped entry reaches it
 * (measured: zero entries have an empty `base`).
 *
 * Provenance: src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php
 * (tf_bot_infos) + 2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php
 * (the fold that changed it), 2026_09_14_200040_create_tf_stars_business_tables.php
 * (tf_saved_star_gifts), schema/sources/TL_telegram_v227.tl lines 697 / 2024.
 */
final class MirrorDerivedKeyPlacesRowTest extends TestCase
{
    /** Bots migration — the shipped tf_bot_infos DDL. */
    private const BOTS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200041_create_tf_bots_tables.php';

    /**
     * The fold that changed `tf_bot_infos` AFTERWARDS.
     *
     * Named and applied after `BOTS_MIGRATION` because the shipped
     * `tf_bot_infos` is a TWO-migration table, and booting the first half alone
     * hands these tests a schema the package does not ship — measured in
     * `MirrorFactWriterUnplaceableKeyDegradesTest`, which failed exactly this way.
     */
    private const BOTS_DESCRIPTION_FOLD_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php';

    /** Stars/business migration — the shipped tf_saved_star_gifts DDL. */
    private const STARS_MIGRATION = __DIR__.'/../../src/Laravel/Migrations/2026_09_14_200040_create_tf_stars_business_tables.php';

    private const TL = __DIR__.'/../../schema/sources/TL_telegram_v227.tl';

    private const CATALOG = __DIR__.'/../../src/Schema/schema/mirror-catalog.json';

    /** A botInfo exactly as the wire decodes it: `id` is nowhere in it. */
    private const BOT_INFO_PAYLOAD = [
        '_' => 'botInfo',
        'user_id' => 777123,
        'has_preview_medias' => true,
        'description' => 'a bot',
    ];

    /** A savedStarGift: no top-level `id`; the identity is the nested StarGift. */
    private const SAVED_STAR_GIFT_PAYLOAD = [
        '_' => 'savedStarGift',
        'date' => 1735689600,
        'unsaved' => false,
        'gift' => [
            '_' => 'starGiftUnique',
            'id' => 9911,
            'gift_id' => 4400,
            'num' => 17,
            'title' => 'Gift',
        ],
    ];

    private const ACCOUNT = 7;

    /**
     * WHY THIS NO LONGER ASSERTS A THROW, AND WHY THAT IS NOT A WEAKENING.
     *
     * ## THE PREMISE WAS TRUE, AND THE FOLD MADE IT FALSE
     *
     * This method used to pin rule 5's THROW, and the throw was correct when it
     * was written: `tf_bot_infos` had an EMPTY `base`, because
     * `BotInfo.description` was modelled as a CHILD table
     * (`tf_bot_infos_description`) rather than a column. Empty base means no
     * `base[0]` to derive from, so the resolver reached rule 5 and refused.
     *
     * `2026_10_11_000100_fold_bot_infos_description_onto_the_bot_info_fact.php`
     * then removed that fabricated table and folded the value onto the fact row,
     * and the catalog followed it — `base` is now:
     *
     * ```
     * [["description", "TEXT NOT NULL"]]
     * ```
     *
     * MEASURED on the stripped catalog, the resolver's own rules now select a
     * DIFFERENT arm, and the trace is arithmetic on `base[0]` alone:
     *
     * ```
     * base[0]=description ; is id? n ; is peer? n ; ends _id? n
     * => falls to RULE 4 (fabricate), singularize(tf_bot_infos)=bot_info, key=bot_info_id
     * => RULE 5 (throw) needs base===[] which is now FALSE post-fold.
     * ```
     *
     * ## SO THE RESOLVER IS NOT WRONG — THE FIXTURE'S PREMISE WAS
     *
     * `deriveNaturalKey()` is behaving exactly as documented: rule 4 owns
     * "non-empty `base` whose `base[0]` is not self-identifying", and it
     * fabricates `{singular}_id`. Nothing here is a regression, and "fixing" the
     * resolver to throw here would break `tf_todo_lists` (`base[0]='title'`) and
     * `tf_reactions` (`base[0]='emoticon'`), which are the entries rule 4 was
     * MEASURED to keep — see that method's own comment. Verified across the whole
     * shipped catalog: zero entries have an empty `base`, so rule 5's throw is
     * armed and unreachable on the shipped dial. Its behaviour stays pinned where
     * it belongs, by a synthetic empty-base probe in
     * `tests/Schema/MirrorScopedKeyDeclarationTest::test_a_table_with_no_identity_and_no_declaration_throws()`.
     *
     * ## THE PROPERTY IS UNCHANGED AND STILL ASSERTED — THE MECHANISM MOVED
     *
     * The guarantee this method exists to carry is NOT "the resolver throws". It
     * is the sentence in its own failure message: a fabricated key is how rows
     * get dropped. That property is asserted here on its own terms, and the
     * writer now enforces it STRICTLY STRONGER than the throw ever did — the
     * fabricated `bot_info_id` is caught at the WRITE and the row is refused:
     *
     * ```
     * tf_bot_infos: row cannot cover its primary key (account_id, id) —
     *   uncovered key columns: id — row skipped, not stored under a fabricated key
     * ```
     *
     * A rule-5 THROW aborts the whole batch (a `MirrorSchemaException` escapes
     * `placeRow()`, which `write()` guards only against `QueryException` — see
     * `MirrorFactWriterUnplaceableKeyDegradesTest`'s class docblock). The
     * write-level refusal degrades ONE row. The original 2026-10-03 change
     * traded a fabricated key for an aborting throw; post-fold the code lands on
     * a per-row refusal, which is the strictly better of those two behaviours.
     *
     * So the assertions below are NOT a relaxation. Both halves of the original
     * are still asserted, and the substituted one is tighter in the way that
     * matters: the fabricated name is now caught IN THE WRITE PATH, where a row
     * can actually be stored, rather than in the resolver, one layer earlier.
     */
    public function test_bot_info_row_places_under_its_derived_key(): void
    {
        // BEFORE — the pre-fix catalog cannot express an identity, so the writer
        // refuses the fabricated key instead of storing under it.
        $preFix = ShippedStoreSchema::capsuleFor(
            self::BOTS_MIGRATION,
            self::BOTS_DESCRIPTION_FOLD_MIGRATION,
        )->getConnection();

        $before = $this->write(
            $preFix,
            $this->catalogWithoutDerivedKeys(),
            'tf_bot_infos',
            self::BOT_INFO_PAYLOAD,
        );

        // The fabricated key is named in the CLUE, so it stays greppable and an
        // engineer can see the wire has no such field. The old throw said the
        // same thing in its message; here the writer says it per row.
        self::assertContains(
            "tf_bot_infos: keying column 'bot_info_id' missing from payload — cannot place the fact",
            $before['clues'],
            'the pre-fix catalog must name the column the heuristic invented, so the old key is greppable',
        );

        // AND the write path must REFUSE it — the guarantee that actually matters,
        // asserted on the shipped schema rather than a stub.
        self::assertContains(
            'tf_bot_infos: row cannot cover its primary key (account_id, id) '
            .'— uncovered key columns: id — row skipped, not stored under a fabricated key',
            $before['fkClues'],
            'a fabricated key must never reach storage — that refusal is what keeps a row from being dropped under one',
        );

        // The guarantee the old test carried, KEPT and not traded for anything:
        // the pre-fix catalog still places NOTHING.
        self::assertSame(
            0,
            $preFix->table('tf_bot_infos')->count(),
            'with the pre-fix catalog the botInfo row must NOT place — a fabricated key is how rows get dropped',
        );

        // AFTER — the shipped catalog, derived key and all, on its own schema:
        // re-ingesting the same fact into one database would collide on the
        // primary key, which is the store's idempotence job, not the point here.
        $db = ShippedStoreSchema::capsuleFor(
            self::BOTS_MIGRATION,
            self::BOTS_DESCRIPTION_FOLD_MIGRATION,
        )->getConnection();
        $after = $this->write($db, $this->shippedCatalog(), 'tf_bot_infos', self::BOT_INFO_PAYLOAD);
        self::assertSame([], $after['clues'], 'a botInfo must place with no clue at all');
        self::assertSame([], $after['fkClues'], 'a botInfo must place with no write clue at all');

        $row = $db->table('tf_bot_infos')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row, 'the botInfo row must be present after the fix');
        self::assertSame(777123, (int) $row->id, 'the key must carry the bot user_id the wire supplied');
        self::assertSame(1, (int) $row->has_preview_medias, 'the flag bit must be stored, not defaulted');

        // The child fact is keyed by the SAME value, which is what makes the
        // shipped `tf_bot_infos_user_id` primary(['account_id','id']) reachable.
        self::assertSame(
            1,
            $db->table('tf_bot_infos_user_id')->where('account_id', self::ACCOUNT)->where('id', 777123)->count(),
            'the child fact row must link to its parent by the derived key',
        );
    }

    public function test_saved_star_gift_row_places_under_its_nested_derived_key(): void
    {
        $preFix = ShippedStoreSchema::capsuleFor(self::STARS_MIGRATION)->getConnection();
        $before = $this->write($preFix, $this->catalogWithoutDerivedKeys(), 'tf_saved_star_gifts', self::SAVED_STAR_GIFT_PAYLOAD);
        self::assertSame(
            0,
            $preFix->table('tf_saved_star_gifts')->count(),
            'with the pre-fix catalog the savedStarGift row must NOT place — otherwise this test proves nothing',
        );
        self::assertContains(
            "tf_saved_star_gifts: keying column 'saved_star_gift_id' missing from payload — cannot place the fact",
            $before['clues'],
        );

        $db = ShippedStoreSchema::capsuleFor(self::STARS_MIGRATION)->getConnection();
        $after = $this->write($db, $this->shippedCatalog(), 'tf_saved_star_gifts', self::SAVED_STAR_GIFT_PAYLOAD);
        // A union child legitimately clues on the flag-gated peers its variant
        // does not carry; what must NOT appear is a keying clue, and what must
        // appear is no write failure.
        self::assertSame(
            [],
            array_values(array_filter($after['clues'], static fn (string $c): bool => str_contains($c, 'keying column'))),
            'a savedStarGift must place with no keying clue',
        );
        self::assertSame([], $after['fkClues'], 'a savedStarGift must place with no write clue at all');

        $row = $db->table('tf_saved_star_gifts')->where('account_id', self::ACCOUNT)->first();
        self::assertNotNull($row, 'the savedStarGift row must be present after the fix');
        self::assertSame(9911, (int) $row->id, 'the key must carry the NESTED StarGift.id, not a top-level id');
        self::assertSame(1735689600, (int) $row->date, 'the real base columns must still be stored');

        // The nested fact the key is derived from must place under the SAME
        // key, which is what the shipped tf_saved_star_gifts_gift
        // primary(['account_id','id']) — and its own comment — require.
        self::assertSame(
            1,
            $db->table('tf_saved_star_gifts_gift')->where('account_id', self::ACCOUNT)->where('id', 9911)->count(),
            'the nested StarGift row must place, keyed by its own id',
        );
    }

    /**
     * The derivation must be CHECKED, not trusted: a keyFrom path naming a
     * value the payload cannot carry has to fail at catalog LOAD, exactly like
     * a `key` naming one. This is the guard that stops `keyFrom` from becoming
     * the same papering-over the `key` field was banned for.
     */
    public function test_a_derivation_the_payload_cannot_carry_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("keyFrom 'id' => 'not_a_param'");

        MirrorCatalog::fromEntries([
            'tf_bogus' => [
                'tl' => 'BotInfo',
                'ctors' => ['botInfo'],
                'key' => ['id'],
                'keyFrom' => ['id' => 'not_a_param'],
                'base' => [],
                'bools' => [],
                'children' => [],
            ],
        ]);
    }

    /**
     * A derivation may only reach one level down, and only through a field the
     * entry declares `FK→Type` — the target type is what makes `gift.id`
     * checkable. A deeper path is refused rather than guessed.
     */
    public function test_a_deeper_derivation_path_is_rejected_at_load(): void
    {
        $this->expectExceptionMessage("only 'param' and 'fkField.param' are derivable");

        MirrorCatalog::fromEntries([
            'tf_bogus' => [
                'tl' => 'SavedStarGift',
                'ctors' => ['savedStarGift'],
                'key' => ['id'],
                'keyFrom' => ['id' => 'gift.attributes.gift'],
                'base' => [['gift', 'FK→StarGift']],
                'bools' => [],
                'children' => [],
            ],
        ]);
    }

    /**
     * Run the real write path for one table and report what happened.
     *
     * @param  ConnectionInterface  $db
     * @param  array<string, mixed>  $payload
     * @return array{clues: list<string>, fkClues: list<string>, inserted: int}
     */
    private function write($db, MirrorCatalog $catalog, string $tfName, array $payload): array
    {
        $resolver = new MirrorTableResolver($catalog, TlParser::parseFile(self::TL));
        $fact = (new MirrorFactDecomposer($resolver, $catalog))->decompose($tfName, self::ACCOUNT, $payload);
        $write = (new MirrorFactWriter)->write($db, $fact['rows']);

        return ['clues' => $fact['clues'], 'fkClues' => $write['fkClues'], 'inserted' => $write['inserted']];
    }

    private function shippedCatalog(): MirrorCatalog
    {
        return MirrorCatalog::load(self::CATALOG, TlParser::parseFile(self::TL));
    }

    /**
     * The real catalog with the expressed derivations REMOVED — the state the
     * tree shipped before this fix, derived from the real file so it cannot
     * drift into a fiction.
     */
    private function catalogWithoutDerivedKeys(): MirrorCatalog
    {
        $entries = json_decode((string) file_get_contents(self::CATALOG), true, 512, JSON_THROW_ON_ERROR);
        foreach (['tf_bot_infos', 'tf_saved_star_gifts'] as $tfName) {
            unset($entries[$tfName]['key'], $entries[$tfName]['keyFrom']);
        }

        $path = sys_get_temp_dir().'/teleframe-mirror-catalog-prefix.json';
        file_put_contents($path, json_encode($entries, JSON_THROW_ON_ERROR));

        try {
            return MirrorCatalog::load($path, TlParser::parseFile(self::TL));
        } finally {
            @unlink($path);
        }
    }
}
