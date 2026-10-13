<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use MeRezaRezaei\Teleframe\Schema\Generator\TlParser;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorCatalog;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTable;
use MeRezaRezaei\Teleframe\Schema\Mirror\MirrorTableResolver;
use PHPUnit\Framework\TestCase;

/**
 * Cycle 31 gate: the mirror catalog and the shipped migration set must agree
 * on table NAMES.
 *
 * `src/Schema/schema/mirror-catalog.json` is the curated, hand-owned source
 * of truth for what a TL constructor persists into: `MirrorCatalog::tableForCtor()`
 * resolves a decoded payload ctor straight to a `tf_*` table name, and
 * `MirrorTableResolver` then resolves that name to a DDL graph. Nothing on
 * that path checks that the resolved table is ever CREATED.
 *
 * The failure this catches: a clean `php artisan migrate` returns green, and
 * the first live update carrying a Reaction / ForumTopic / StoryItem /
 * Wallpaper / MessageEntity / MessageMedia / MessageAction / DialogFilter /
 * SavedReactionTag row dies at runtime with a no-such-table error on a host
 * that was told the install succeeded. No migrate-time signal, no startup
 * signal — only the first write.
 *
 * Before this test the catalog declared 36 top-level tables and only 27 had
 * a `Schema::create` anywhere in the shipped set; the nine missing ones were
 * only discoverable by reading both files by hand.
 *
 * The name set compared here is the RESOLVER's, not the raw JSON's — see
 * `catalogTables()`, which documents why that distinction is load-bearing and
 * what it cost when it was ignored (39 declared keys vs 40 resolved tables).
 */
final class MirrorCatalogShipsInMigrationsTest extends TestCase
{
    /** Repo root — this suite is filesystem-only, no Laravel boot needed. */
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const CATALOG = self::PACKAGE_ROOT.'/src/Schema/schema/mirror-catalog.json';

    private const SHIP_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * The catalog tables no shipped migration created before this cycle.
     * Listed explicitly so the key-shape assertion stays scoped to the gap
     * this cycle closed rather than growing to cover unrelated divergences.
     *
     * Was NINE entries; it is now EIGHT. `tf_message_entities` left on
     * 2026-10-04: it was deleted from `mirror-catalog.json` AND from
     * `2026_09_29_000100` in the same change, because nothing ever wrote it —
     * measured over ten payload shapes the shipped decomposer emits 0 rows
     * into it and 18 into the positioned sibling `tf_messages_entities` — and
     * its facts already ship there, stored without loss. A name the catalog has
     * dropped is not a key-shape divergence; keeping it here would make this
     * gate ask the resolver about a table that no longer exists.
     *
     * @var list<string>
     */
    private const GAP_TABLES = [
        'tf_dialog_filters',
        'tf_forum_topics',
        'tf_message_actions',
        'tf_message_medias',
        'tf_reactions',
        'tf_saved_reaction_tags',
        'tf_story_items',
        'tf_wallpapers',
    ];

    /**
     * Depth-2-and-deeper mirror tables the resolver synthesizes but the shipped
     * dial does not create, measured 2026-09-29.
     *
     * These have NO write path: `MirrorFactDecomposer::decomposeChildren()`
     * iterates the direct children of a resolved parent and `childRow()` does
     * not recurse. Pinned as a count so the class cannot grow silently — see
     * `test_deep_child_gap_is_pinned_because_nothing_writes_them`.
     *
     * ## RE-MEASURED 2026-10-11: 206 -> 238, and WHY THAT IS NOT A NEW DEFECT
     *
     * `05e1488d` ("fold six fabricated scalar child tables") added four catalog
     * parents that ship real tables: `tf_updates`, `tf_channel_updates`,
     * `tf_update_differences` and `tf_channels`. Every CHILD those four entries
     * declare corresponds to a shipped `Schema::create` — measured, all 35 of
     * them (`tf_updates_{pts,pts_count,qts,date,max_id,top_msg_id,folder_id,
     * still_unread_count,status,action}`, `tf_channel_updates_{pts,timeout,
     * dialog}`, `tf_update_differences_{date,seq,pts,state}`, and the eighteen
     * `tf_channels_*` legs) — so the depth-0/1 assertion above stayed at 0 and
     * correctly so.
     *
     * The +32 are what those parents REACH, not what they declare, and every one
     * is the resolver's union decomposition of a genuinely nested wire union:
     *
     *  - 28 under `tf_updates_action`, from `FK->SendMessageAction`
     *    (`messageActionText`, `messageActionRichMessage` and the
     *    `RichMessage`/`PageBlocks`/`PageList`/`PageCaption` tree beneath it);
     *  - 4 under `tf_channels_color` / `tf_channels_profile_color`, from
     *    `FK->PeerColor`'s `colors` and `dark_colors` vectors.
     *
     * Both are the SAME shape as the 206 this constant already pinned:
     * depth >= 2, synthesized by `unionDecompose()`, and unwritten because
     * `childRow()` does not recurse. Nothing here is a fabricated scalar child
     * of the kind `05e1488d` removed — that fold moved VALUES up onto their
     * parent; these are nested OBJECTS, and a relational store has to model
     * them somewhere.
     *
     * So the count moved because the DDL graph grew by four real parents, which
     * is precisely what the pin exists to make visible. It was re-measured on
     * the shipped dial (sqlite + mysql agree; `bin/generate-mirror-shape.php`
     * boots both) and set to the new measured value rather than loosened into a
     * range.
     */
    private const DEEP_UNSHIPPED_CHILD_COUNT = 238;

    /**
     * Every top-level table the catalog declares must be created by a
     * `Schema::create` in the shipped migration set, by EXACT name.
     *
     * The comparison is exact on purpose. The singular/plural split
     * (`tf_message_medias` in the catalog vs `tf_messages_media` in the
     * message-scoped child migration) is NOT a spelling variant of one
     * table: the catalog parents are context-free, keyed on a synthetic id
     * (`message_media_id`, `reaction_id`, `saved_reaction_tag_id`,
     * `message_action_id`), while the `tf_messages_*` tables are positioned
     * children of `tf_messages` keyed (account_id, id, position). Renaming
     * one into the other would silently repoint the resolver at the wrong
     * primary key, so the gap is closed by CREATING the catalog tables, not
     * by reconciling names.
     *
     * `tf_message_entities` was the sharpest instance of that split, and is the
     * reason this paragraph exists. It was RESOLVED BY DELETION on 2026-10-04
     * rather than by renaming: nothing ever wrote it and its facts already ship
     * in `tf_messages_entities`, keyed with `position` and stored without loss.
     */
    public function test_every_catalog_table_is_created_by_a_shipped_migration(): void
    {
        $catalogTables = $this->catalogTables();
        $shippedTables = $this->shippedTables();

        self::assertNotEmpty($catalogTables, 'the resolved catalog declares no tables — the catalog path moved?');
        self::assertNotEmpty($shippedTables, 'no Schema::create found in src/Laravel/Migrations');

        $missing = array_values(array_diff($catalogTables, $shippedTables));
        sort($missing);

        self::assertSame(
            [],
            $missing,
            sprintf(
                "mirror-catalog.json declares %d table(s) that no shipped migration creates: %s.\n"
                .'These resolve to a table name at write time (MirrorCatalog::tableForCtor) and then fail on insert.',
                count($missing),
                $missing === [] ? 'none' : implode(', ', $missing),
            ),
        );
    }

    /**
     * A shipped table under the catalog's exact name must be the SAME table
     * the resolver will write to: same primary key.
     *
     * This is the assertion the singular/plural trap actually needs. The
     * catalog parent `tf_message_medias` and the message-scoped child
     * `tf_messages_media` are different tables — one is context-free and
     * keyed on a synthetic `message_media_id`, the other is a positioned
     * child of `tf_messages` keyed (account_id, id, position). A name-only
     * check cannot tell them apart, and reconciling them by renaming would
     * repoint the writer at the wrong key and fail on every insert. Pinning
     * the key shape makes that rename impossible to do by accident.
     *
     * The entity pair was the worst case and is no longer a pair:
     * `tf_message_entities` is GONE (catalog and dial, 2026-10-04) and only
     * `tf_messages_entities` remains. Its successor lesson is recorded in
     * `GAP_TABLES` above — a redundant table is deleted; a live one is never
     * renamed into it.
     *
     * Scoped to the tables this cycle closed the gap on. Four PRE-EXISTING
     * catalog tables already ship under the catalog name with a DIFFERENT
     * primary key than MirrorTableResolver derives. Only one of them was a
     * key-NAMING bug and is fixed: tf_business_chat_links, where the
     * resolver fabricated `business_chat_link_id` because the real
     * constructor param `link` does not end in `_id`. The catalog now
     * declares `"key": ["link"]` explicitly.
     *
     * The other three are NOT naming bugs and no rename or ALTER fixes them,
     * because the value does not exist on the wire: tf_bot_infos and
     * tf_saved_star_gifts ship a designer key (`id`, derived from a nested
     * `StarGift.id` / the bot's `user_id`) while the constructor carries
     * neither; tf_channel_participants ships a stricter key than the
     * constructor supplies, needing a `channel_id` that exists only in the
     * enclosing `channels.channelParticipant.chats` context. Those need
     * key DERIVATION, not a DDL change, and are pinned by name in
     * MirrorResolverKeyMatchesShippedDdlTest so the class cannot grow
     * silently. They are deliberately not asserted as regressions here.
     */
    public function test_gap_tables_ship_with_the_key_the_resolver_writes(): void
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $shipped = $this->shippedPrimaryKeys();

        $mismatched = [];
        foreach ($resolver->resolveAll(self::GAP_TABLES) as $table) {
            $expected = array_merge(['account_id'], $table->keyColumns);
            if ($table->positioned) {
                $expected[] = 'position';
            }
            if (! isset($shipped[$table->tfName])) {
                $mismatched[$table->tfName] = ['expected' => $expected, 'shipped' => null];

                continue;
            }
            if ($shipped[$table->tfName] !== $expected) {
                $mismatched[$table->tfName] = ['expected' => $expected, 'shipped' => $shipped[$table->tfName]];
            }
        }

        self::assertSame(
            [],
            $mismatched,
            'gap table(s) ship with a primary key that is not the one MirrorTableResolver writes: '
            .json_encode($mismatched),
        );
    }

    /**
     * Every table the WRITE PATH can produce must be created by a shipped
     * migration — not just the catalog's own top-level parents.
     *
     * This is the hole that let `tf_messages_rich_message` through. The
     * assertion above compares the catalog's RESOLVED name set against the
     * shipped `Schema::create` names, so it can only ever see the declared
     * parents.
     * But `MirrorTableResolver` SYNTHESIZES a further ~360 tables from those
     * parents' `base`/`children` entries, named `{parent}_{field}`
     * (MirrorTableResolver.php:255), and `MirrorFactDecomposer::decomposeChildren()`
     * writes a row for every one of them. Those children are in no JSON key
     * list, so the name-set comparison could not have seen them.
     *
     * Scope is deliberately DEPTH 1 — the direct children of a catalog parent.
     * That is exactly the set `decomposeChildren()` walks: it iterates
     * `$table->children` of the resolved PARENT and hands each to `childRow()`,
     * which does not recurse. So a depth-2+ table has no write path at all
     * today, and asserting on it would pin 206 permanently-absent tables as
     * "missing" forever — noise that trains the reader to ignore this test.
     * `test_deep_child_gap_is_pinned_because_nothing_writes_them` covers the
     * rest, as a growth alarm rather than a red list.
     *
     * Measured on 2026-09-29, before this assertion existed: exactly ONE
     * depth-1 table was unshipped, `tf_messages_rich_message` — the table
     * `2026_09_29_000200_create_tf_messages_rich_message_table.php` creates.
     */
    public function test_every_write_reachable_child_table_is_created_by_a_shipped_migration(): void
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $shipped = $this->shippedTables();

        $writeReachable = [];
        foreach ($resolver->resolveAll($catalog->tableNames()) as $parent) {
            foreach ($parent->children as $child) {
                // A shared catalog node (tf_message_medias, tf_chats, …) is
                // written through its OWN ctor route, and decomposeChildren
                // skips it (`$child->parentTf !== $tfName`). The synthesized
                // `{parent}_{field}` children are the ones it writes.
                if ($child->parentTf !== $parent->tfName) {
                    continue;
                }
                $writeReachable[$child->tfName] = true;
            }
        }

        self::assertNotEmpty($writeReachable, 'the resolver synthesized no direct children — the resolver path moved?');

        $missing = array_values(array_diff(array_keys($writeReachable), $shipped));
        sort($missing);

        self::assertSame(
            [],
            $missing,
            sprintf(
                'MirrorFactDecomposer::decomposeChildren() writes a row for %d direct mirror child(ren), '
                .'and %d of them no shipped migration creates: %s.%s'
                .'The insert raises no-such-table on the first payload carrying the fact, and '
                .'MirrorFactWriter deliberately does not absorb it (an empty column listing means the '
                .'TABLE is missing, not the field).',
                count($writeReachable),
                count($missing),
                $missing === [] ? 'none' : implode(', ', $missing),
                "\n",
            ),
        );
    }

    /**
     * Grandchild-and-deeper mirror tables are resolved but never written, and
     * that is pinned here so the class cannot GROW unnoticed.
     *
     * `decomposeChildren()` walks the direct children of a resolved parent and
     * `childRow()` does not recurse, so no depth-2+ table has a write path in
     * the shipped code. They are real DDL-graph nodes with no table — the same
     * shape as this cycle's defect, one level further from any write. When the
     * writer does recurse, the assertion above immediately names them, because
     * it walks the same graph.
     *
     * Pinned as a COUNT, not a list: a 206-name list is a wall nobody reads,
     * and the number is what carries the signal. If this count rises, a new
     * deep gap appeared and the assertion above will be red for it too.
     */
    public function test_deep_child_gap_is_pinned_because_nothing_writes_them(): void
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);
        $resolver = new MirrorTableResolver($catalog, $scheme);

        $depth = [];
        $walk = static function (MirrorTable $table, int $level) use (&$walk, &$depth): void {
            $depth[$table->tfName] = $level;
            foreach ($table->children as $child) {
                $walk($child, $level + 1);
            }
        };
        foreach ($resolver->resolveAll($catalog->tableNames()) as $parent) {
            $walk($parent, 0);
        }

        $shipped = $this->shippedTables();

        $deepMissing = 0;
        $oneLevelMissing = 0;
        foreach (array_diff(array_keys($depth), $shipped) as $name) {
            if ($depth[$name] <= 1) {
                $oneLevelMissing++;

                continue;
            }
            $deepMissing++;
        }

        self::assertSame(
            0,
            $oneLevelMissing,
            'a depth-0/1 unshipped table is a WRITE defect, not a dormant one — '
            .'test_every_write_reachable_child_table_is_created_by_a_shipped_migration names it too, '
            .'but do NOT read this as "it just passed, so these are the same gap seen twice": the two '
            .'assertions are independent and PHPUnit runs test methods in file order, so the sibling may '
            .'report the SAME table red, fail, or not have run at all by the time this one fires. '
            .'MEASURED 2026-10-11 by breaking this invariant in a scratch copy (one extra child on the '
            .'depth-0 parent tf_updates): both tests went red, so "it just passed" was untrue here. '
            .'This one is the stronger claim — it fires from the FULL resolver graph, while the sibling '
            .'only walks the direct children of each catalog parent.',
        );
        self::assertSame(
            self::DEEP_UNSHIPPED_CHILD_COUNT,
            $deepMissing,
            'the dormant depth-2+ gap changed size (was '.self::DEEP_UNSHIPPED_CHILD_COUNT.' on 2026-09-29): '
            .'the resolver synthesized new deep children. They are still unwritten, but the count moving '
            .'means the DDL graph changed and the write path needs re-reading before anyone relies on this pin',
        );
    }

    /**
     * Every table name passed to `Schema::create(...)` in the shipped set,
     * mapped to the column list of its `$table->primary([...])` (empty when
     * the block declares no composite primary).
     *
     * @return array<string, list<string>>
     */
    private function shippedPrimaryKeys(): array
    {
        $keys = [];
        foreach (glob(self::SHIP_DIR.'/*.php') ?: [] as $file) {
            // COMMENTS ARE STRIPPED FIRST, and that is the whole point.
            //
            // `2026_10_04_000895_widen_tf_wallpapers_id_to_bigint.php` QUOTES the
            // original create in its docblock:
            //
            //     line 163   Schema::create('tf_wallpapers', …)   $table->integer('id');
            //     line 177   Schema::create('tf_wallpapers_settings', …)
            //
            // Parsing the raw file treated those two PROSE LINES as creates. Because
            // the loop assigns `$keys[$name] = …` and this file sorts AFTER the one
            // that really creates the table, the quoted line OVERWROTE the correct
            // entry with a block that contains no `primary([...])`. The gate then
            // reported:
            //
            //     {"tf_wallpapers":{"expected":["account_id","id"],"shipped":[]}}
            //
            // which reads as a real fidelity gap — a shipped table missing the key
            // its resolver writes — and is entirely a parsing artefact.
            //
            // The failure mode is worse than the false positive it produced: a
            // migration that documents a table WITHOUT quoting its key would have
            // passed here while that table genuinely had no primary key. A gate that
            // reads prose cannot tell those two apart, so it must not read prose.
            $body = $this->stripPhpComments((string) file_get_contents($file));
            $offset = 0;
            while (($start = strpos($body, "Schema::create('", $offset)) !== false) {
                $start += strlen("Schema::create('");
                $end = strpos($body, "'", $start);
                if ($end === false) {
                    break;
                }
                $name = substr($body, $start, $end - $start);
                $offset = $end;

                $blockEnd = strpos($body, "Schema::create('", $offset);
                $block = $blockEnd === false ? substr($body, $offset) : substr($body, $offset, $blockEnd - $offset);

                $keys[$name] = $this->primaryKeyColumns($block);
            }
        }

        return $keys;
    }

    /**
     * Remove `//`, `#` and `/* *\/` comments while preserving every offset.
     *
     * Offsets are preserved because the callers slice this string to delimit
     * `Schema::create(…)` blocks; collapsing lines or changing length would move
     * every boundary. Comments become equivalent runs of spaces and newlines
     * instead, so the remaining text keeps its exact geometry.
     */
    private function stripPhpComments(string $source): string
    {
        $out = '';
        $length = strlen($source);
        $i = 0;

        while ($i < $length) {
            $two = substr($source, $i, 2);

            // Line comment: consume to end of line, keep the newline itself.
            if ($two === '//' || ($source[$i] ?? '') === '#') {
                while ($i < $length && $source[$i] !== "\n") {
                    $out .= ' ';
                    $i++;
                }

                continue;
            }

            // Block comment: consume to the terminator, keeping every newline.
            if ($two === '/*') {
                $out .= '  ';
                $i += 2;

                while ($i < $length) {
                    if (substr($source, $i, 2) === '*/') {
                        $out .= '  ';
                        $i += 2;
                        break;
                    }

                    $out .= ($source[$i] === "\n") ? "\n" : ' ';
                    $i++;
                }

                continue;
            }

            $out .= $source[$i];
            $i++;
        }

        return $out;
    }

    /**
     * @return list<string>
     */
    private function primaryKeyColumns(string $block): array
    {
        $marker = '$table->primary(';
        $start = strpos($block, $marker);
        if ($start === false) {
            return [];
        }
        $start += strlen($marker);
        $open = strpos($block, '[', $start);
        $end = strpos($block, ']', $open === false ? $start : $open);
        if ($open === false || $end === false) {
            return [];
        }

        $cols = [];
        foreach (explode(',', substr($block, $open + 1, $end - $open - 1)) as $raw) {
            $col = trim(trim($raw), "'\"");
            if ($col !== '') {
                $cols[] = $col;
            }
        }

        return $cols;
    }

    /**
     * Top-level table names the CATALOG RESOLVES, read through
     * `MirrorCatalog::load()` rather than off the raw JSON object keys.
     *
     * ## WHY NOT THE RAW JSON KEYS — this method used to read them, and that
     * was the defect this whole path existed to catch, hiding behind itself
     *
     * `mirror-catalog.json` declares **39** table keys. The RESOLVER produces
     * **40**, because `MirrorCtorSplitter` (`src/Schema/Mirror/MirrorCtorSplitter.php`)
     * mechanically derives `tf_messages_service` from the Layer-227
     * `messageService` ctor and installs it as a 40th entry. Measured on this
     * tree, 2026-10-13:
     *
     *     resolver tableNames(): 40
     *     raw json keys:        39
     *     in resolver not raw:  tf_messages_service
     *     in raw not resolver:  (none)
     *
     * So a raw-JSON read of the name set is one short, and
     * `test_every_catalog_table_is_created_by_a_shipped_migration` — the gate
     * whose entire stated purpose is "the catalog and the shipped migration set
     * must agree on table NAMES" — was silently NEVER ASKING ABOUT
     * `tf_messages_service`. The one table the class's own docblock most wants
     * covered is the one it could not see: `MirrorCtorSplitter` exists because
     * Message and MessageService are disjoint ctor variants that must not share
     * one nullable-column table (spec §4 decision B), which makes it the
     * highest-consequence table in the file to have an unverified migration.
     *
     * It happened to ship (`2026_09_14_200010_create_tf_messages_tables.php`:715),
     * so the blind spot cost nothing YET. A gate that is blind for the right
     * reason today is blind for the wrong reason the day the split moves, and
     * nothing in this class would have noticed.
     *
     * The 40 is the number the RESOLVER writes to, so it is the number a
     * name-set gate must compare. `MirrorCatalog::load()` is also the exact
     * authority `MirrorCatalog::tableForCtor()` resolves a decoded payload
     * ctor against — the same class-level contract this test is guarding — so
     * reading through it makes the gate measure the resolver's own names rather
     * than a transcription of its input.
     *
     * `mirror-catalog.json` is hand-owned and NOT `@generated`; the 39-vs-40
     * gap is the DESIGN (one merged entry, split mechanically downstream), so
     * the right fix is to stop reading around the loader, never to add a
     * hand-written `tf_messages_service` entry that would duplicate — and then
     * silently override — the mechanical derivation.
     *
     * @return list<string>
     */
    private function catalogTables(): array
    {
        $scheme = TlParser::parseFile(self::PACKAGE_ROOT.'/schema/sources/TL_telegram_v227.tl');
        $catalog = MirrorCatalog::load(self::CATALOG, $scheme);

        $names = array_map('strval', $catalog->tableNames());
        sort($names);

        return $names;
    }

    /**
     * Every table name passed to `Schema::create(...)` in the shipped set.
     *
     * Regex-free scan: every call site in this repo spells the argument
     * `Schema::create('<table>', function (Blueprint $table) {`, so the
     * literal marker plus the next quote delimits the name exactly.
     *
     * @return list<string>
     */
    private function shippedTables(): array
    {
        $files = glob(self::SHIP_DIR.'/*.php') ?: [];
        self::assertNotEmpty($files, 'no shipped migrations found in '.self::SHIP_DIR);

        $tables = [];
        foreach ($files as $file) {
            $body = (string) file_get_contents($file);
            $offset = 0;
            while (($start = strpos($body, "Schema::create('", $offset)) !== false) {
                $start += strlen("Schema::create('");
                $end = strpos($body, "'", $start);
                if ($end === false) {
                    break;
                }
                $tables[substr($body, $start, $end - $start)] = true;
                $offset = $end;
            }
        }

        $names = array_keys($tables);
        sort($names);

        return $names;
    }
}
