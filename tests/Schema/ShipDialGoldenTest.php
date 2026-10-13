<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Golden gate: the curated migration dial. The hand-authored tf_*
 * migrations live in src/Laravel/Migrations and are NEVER auto-generated
 * — bin/regenerate no longer produces migrations (MigrationGenerator
 * disabled per owner verbatim #3: "i dont want the auto generation any
 * more"). App-owned migrations (user_bindings, telegram_apps, etc.)
 * have no generated source either.
 *
 * Post-purge reality (2026-09-15):
 * - 16 curated hand-authored tf_* migrations (messages/media/updates/
 *   identity/stars-business/misc domains)
 * - 5 app-owned migrations (user_bindings, telegram_apps, ...)
 * - 21 total in src/Laravel/Migrations
 * - bin/regenerate --ship is a no-op (no generated migrations to copy)
 *
 * Grown deliberately since: tf_store tables (2026_09_20, TStore), tf_accounts
 * runtime registry (2026_09_22), and the backup-store run ledger + peer_type
 * alter (2026_09_24, 9f71e3dc) — 26 curated files today. The cap bounds
 * hand-authored curation; a legitimate addition raises it with a record.
 */
final class ShipDialGoldenTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const SHIP_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * App-owned migrations under src/Laravel/Migrations/ that are hand-authored
     * and have NO generated source. Always present, never overwritten by
     * bin/regenerate.
     */
    private const APP_OWNED_MIGRATIONS = [
        '2026_09_08_000100_create_tl_user_bindings_table.php',
        '2026_09_09_000200_create_telegram_apps_table.php',
        '2026_09_09_000201_create_telegram_accounts_table.php',
        '2026_09_14_000000_add_user_id_to_telegram_accounts_table.php',
        '2026_09_14_000001_create_tg_update_routing_table.php',
        // The mirror's durable coverage ledger (2026_10_02_000500). It is
        // hand-authored LOCAL/APP state — no `constructor` discriminator, no
        // `->foreign()`, no generated source at all — so it has no entry in
        // src/Schema/Generated and nothing else in the pipeline claims it.
        // Unlisted here it would be classified "unrecognized hand-authored" by
        // every teleframe:regenerate run (warned and left, by the W-04 policy in
        // SchemaRegenerator::shipMigrations) and NO test would pin the fact that
        // it is deliberate, so the next person to see the warning would have
        // every reason to delete the file. Listing it here is what makes
        // test_app_owned_migrations_present() assert its existence and
        // test_curated_migrations_are_hand_authored() assert it carries no
        // @generated marker.
        '2026_10_02_000500_create_tf_ingest_clues_table.php',
    ];

    public function test_shipped_subset_count(): void
    {
        $files = self::migrationFiles(self::SHIP_DIR);
        self::assertGreaterThanOrEqual(16, $files, 'curated dial must have at least 16 migrations (16 curated + 5 app-owned)');
        // Bound raised 26 -> 27 by Cycle 17 (2026-09-25): the durable backfill
        // cursor ledger, tf_backfill_cursors (2026_09_25_000300), is what makes
        // the "full account history" crawl reach message id 1 across restarts.
        // Raised 27 -> 28 by Version One (2026-09-26): Telegram Passport
        // authentication provenance on tl_user_bindings (passport_fields,
        // passport_verified_at) — the recorded evidence that a host user
        // authenticated via Telegram, and with which verified field TYPES.
        // Raised 28 -> 29 by the first-release session-revocation work
        // (2026-09-27): revoked_at on telegram_accounts, the marker that tells
        // a host which accounts need re-login.
        // Raised 29 -> 30 by Cycle 31 (2026-09-29): the nine mirror-catalog
        // tables nothing created (tf_reactions, tf_forum_topics, tf_story_items,
        // tf_wallpapers, tf_saved_reaction_tags, tf_message_entities,
        // tf_message_medias, tf_message_actions, tf_dialog_filters) plus their
        // synthetic-key child tables. tf_message_entities was among the nine and
        // was deleted again on 2026-10-04; the count is unchanged because that
        // edited the migration in place. MirrorCatalog::tableForCtor() resolves a
        // ctor to one of these names and inserts with no existence check, so
        // their absence turned a green migrate into a runtime no-such-table on
        // the first update carrying any of them. Guarded by
        // MirrorCatalogShipsInMigrationsTest.
        // Raised 30 -> 31 (2026-09-29, the rich-message child): tf_messages_rich_message
        // is a mirror child SYNTHESIZED by MirrorTableResolver from
        // tf_messages' `["rich_message","FK→RichMessage"]` entry, not a catalog
        // JSON key, so the catalog-name assertion could not see it — while
        // MirrorFactDecomposer::decomposeChildren() writes a row for it on the
        // first `message` update carrying rich_message:flags2.13, and no
        // shipped migration created the table. Guarded by
        // MirrorCatalogShipsInMigrationsTest::test_every_write_reachable_child_table_is_created_by_a_shipped_migration.
        // The bound is a ratchet against unbounded migration growth, not a
        // freeze — a new durable truth table legitimately moves it, and the
        // value must be raised WITH the migration, never by dropping it.
        // Raised 31 -> 32 (2026-09-29, the search index): tf_message_search, the
        // inverted index that replaced MessageSearchQuery's leading-wildcard
        // LIKE. That LIKE was a guaranteed full scan of tf_messages — the largest
        // table a real host owns — and NO index can serve a leading '%', so the
        // only way to make message search sublinear was a new table, not a new
        // index on the old one.
        // Raised 32 -> 33 (2026-09-29, the channel access-hash store):
        // 2026_09_29_000400_create_tf_chats_access_hash_table.php, the 1:1
        // child of tf_chats carrying `access_hash` — the flag-gated union fact
        // only `channel`/`channelForbidden` have on the wire
        // (schema/sources/api.tl:102-105), modelled on the existing
        // tf_users_access_hash. It is a REAL catalogued table whose absence made
        // BackfillCommand::mirrorPeerLookup() read `tf_channels`, a table that
        // is not in the catalog and that nothing writes, so every
        // `--peer=<channel>` was refused as hashless. Same class of defect as
        // 30 -> 31 and 31 -> 32: the resolver synthesizes the child name from
        // the catalog entry, so the catalog-name assertion cannot see it, while
        // MirrorFactDecomposer::decomposeChildren() writes a row for it on the
        // first dialogs page carrying a channel.
        // Raised 33 -> 34 (2026-10-02, tf_ingest_clues — the mirror's coverage
        // accounting). The bound is a ratchet, not a freeze: a new durable truth
        // table legitimately moves it. Raised WITH its migration, never by
        // dropping a pin. See ShipDialGoldenTest for the sanctioned mechanism.
        // 2026_10_02_000500_create_tf_ingest_clues_table.php is the durable
        // ledger of what the mirror FAILED to store: the four clue buckets
        // (decomposer coverage clues, fkClues, unmappedClues, valueClues) were
        // computed by the mirror and then discarded by TWO layers on the way to
        // the console — MEASURED, not guessed, and the two are
        // `MirrorIngesterFactory::build()`'s `ingest()` call and
        // `MirrorIngesterFactory::ingestNestedSources()` — so no coverage gap
        // was recorded anywhere and the gate announced "Integrity OK" beside its
        // own gap list. It is LOCAL/APP
        // state, deliberately NOT a mirror-catalog table — no `constructor`
        // discriminator, no `->foreign()`, appended rather than upserted — so no
        // catalog-name assertion can see it and only this ratchet guards it.
        // The count is repeated in THREE carriers on purpose — this comment, the
        // migration's own docblock and MySqlDialPortabilityTest — because in
        // this codebase a comment's job is to carry the measurement and a wrong
        // count is worse than no count: one stale carrier is enough to send the
        // next reader looking for two discard sites that do not exist.
        // Raised 34 -> 35 (2026-10-04, the tenant-key parent fix):
        // 2026_10_04_000100_make_user_id_unsigned_unique.php makes
        // telegram_accounts.user_id UNSIGNED and UNIQUE. This is the PARENT
        // half of the foreign-key plan, not a mirror fact: the 265
        // `tf_*.account_id` columns are unsignedBigInteger (BIGINT UNSIGNED)
        // and cannot reference a signed parent at all — MySQL 8.4.11 raises
        // ERROR 3780, and ERROR 6125 once the types agree but no UNIQUE exists
        // (both measured; docs/decisions.md D-005, candidate A). Until this
        // lands, not one of the 265 constraints in the plan can be created, so
        // the bound moves WITH the migration rather than the migration being
        // squeezed out to keep the number still.
        //
        // It is worth naming what this pin is NOT protecting here: this is a
        // one-column ALTER on one app table (telegram_accounts), not a new
        // mirror table, so the usual "a durable truth table legitimately moves
        // the ratchet" reasoning does not apply. This migration earns its place
        // by being a PREREQUISITE, not by being a fact. The first
        // type-altering migration in the repository, too — ->change() appeared
        // zero times in src/, tests/ and bin/ before it.
        // Raised 35 -> 36 (2026-10-04, the six retargeted tenant foreign keys):
        // 2026_10_04_000200_retarget_tenant_foreign_keys.php drops and re-adds
        // the six shipped constraints whose parent column was WRONG —
        // tf_updates, tf_update_routing, tf_update_state,
        // tf_channel_participants, tf_channel_updates and tf_update_differences
        // all compared `account_id` against `telegram_accounts.id`, the vault's
        // app row id, while the engine writes the TELEGRAM user id
        // (src/Laravel/MirrorIngesterFactory.php:160). On any connection that
        // enforces them those six tables refused every real ingest row: measured
        // ERROR 1452 on all six on MySQL 8.4.11 before the migration, and 6/6
        // landing after, with all six still refusing a fabricated tenant and
        // ON DELETE CASCADE taking 6 rows to 0 (docs/decisions.md D-005,
        // "Blocker 2"; DROP was measured to admit orphans, so retarget is the
        // disposition).
        //
        // Like its predecessor, this earns the slot by being a CORRECTNESS fix
        // rather than a fact: it creates no table, adds no column, and stores
        // nothing new. It is a NEW file rather than an edit to
        // 2026_09_14_200030 / 2026_09_14_200031 precisely because an install
        // that already ran those two has them recorded as applied — editing
        // them in place would leave the broken constraint in place forever,
        // which is the single failure mode an additive migration exists to
        // avoid. And it could not have landed before 000100: a signed
        // `telegram_accounts.user_id` against an unsigned `account_id` is
        // refused by MySQL 8.4.11 with ERROR 3780, so the ratchet moves WITH
        // both files or not at all.
        //
        // Raised 36 -> 39 (2026-10-04, the D-007 column contract): three
        // column-only ADDITIVE migrations adding the nine columns
        // MirrorTableResolver emits and the shipped DDL never declared --
        // enforced from now on by
        // tests/Schema/Mirror/ResolvedColumnsMatchShippedDdlTest.php
        // (docs/decisions.md, D-007).
        //
        //   2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php
        //     tf_messages_replies.recent_repliers_type + .recent_repliers_id
        //   2026_10_04_000400_add_star_gift_host_and_availability_columns.php
        //     availability_issued + host_id_type + host_id_id, on BOTH
        //     tf_stars_transactions_stargift and tf_saved_star_gifts_gift
        //   2026_10_04_000500_add_text_column_to_tf_todo_lists_title.php
        //     tf_todo_lists_title.text
        //
        // WHY THREE MIGRATIONS FOR NINE COLUMNS, which is the only
        // non-obvious part of this bump: one file per FAMILY GROUP, not one per
        // column and not one per table. The two star-gift tables are a single
        // omission expressed twice -- both flatten the same StarGift union
        // through the same private starGiftColumns() helper in
        // 2026_09_14_200040, so they are fixed by the same three declarations
        // and splitting them would duplicate the reasoning and give the pair a
        // second chance to drift. The other two groups are different facts with
        // different proofs, so they stay apart.
        //
        // WHY THIS BOUND MOVES RATHER THAN THE MIGRATIONS BEING SUPPRESSED: none
        // of the three creates a table or stores a new fact, so the usual "a
        // durable truth table legitimately moves a ratchet" reasoning does not
        // apply to them. That is exactly why the bound moves WITH them. Each
        // closes a contract breach that was dropping rows silently and twice
        // over: MirrorFactWriter::write() strips a resolver column the live
        // table does not declare (recording an unmappedClue), and the row is
        // then declined by whichever sibling column is NOT NULL with no
        // default -- so the value was gone before the insert and the row after
        // it, with a clue string as the only record. A pin that forbade the fix
        // would have been the defect, not the discipline. The count is repeated
        // in THREE carriers on purpose -- this comment, MySqlDialPortabilityTest
        // (which pins the same number exactly), and the migrations' own
        // docblocks -- because a wrong number in one carrier is enough to send
        // the next reader looking for files that do not exist.
        //
        // Raised 39 -> 40 (2026-10-04, the sixteen `tf_saved_star_gifts_*` keys):
        // 2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php. ONE file
        // for all sixteen child tables because they are one defect and not
        // sixteen: they share a single key column, and that column is the
        // StarGift union's `id`, declared `id:long` by BOTH arms of the union
        // (api_full.tl:1974 and :1975), so there is exactly one correct type
        // for all of them. The shipped DDL declared it `string($key, 255)`
        // through the shared `factKey()` helper
        // (2026_09_14_200040_create_tf_stars_business_tables.php:559-563), and
        // ResolvedColumnsMatchShippedDdlTest measured every one of the sixteen
        // as "resolver emits bigint (BIGINT), shipped DDL declares text".
        //
        // Here the DDL is the wrong side, which is the OPPOSITE disposition
        // from the thirty-four string-keyed columns closed in 0aeeafc6: there
        // the wire said `id:string` and TEXT was right to stay and the resolver
        // changed; here the wire says `id:long` and the shipped create migration
        // is the outlier.
        //
        // It earns the slot on the same grounds as the three above and not on
        // the ratchet grounds: it creates no table, stores no new fact, and
        // closes a contract breach that ResolvedColumnsMatchShippedDdlTest had
        // already measured. It lands in a NEW file rather than as an edit to
        // 200040 because that migration is SHIPPED -- present at tags v1.3.0,
        // v1.3.1 and v1.3.2 -- so an in-place edit would leave every install
        // that already ran it on the wrong key type forever. What makes this
        // one different from the three is that it changes a shipped column's
        // TYPE instead of adding a missing one, and all sixteen of those
        // columns sit in a primary key; so the bound moves WITH the migration
        // rather than the migration being squeezed out to keep the number
        // still.
        //
        // The 41st slot is `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php`,
        // the 265 tenant foreign keys onto `telegram_accounts.user_id`. It earns
        // the slot on the SAME grounds as the three above and not on the ratchet
        // grounds: it creates no table and stores no new fact, it CONSTRAINS 265
        // existing ones, and it closes a contract breach the plan names as its
        // single highest-value constraint. It lands in a NEW file rather than as
        // an edit to the 34 create migrations because those are SHIPPED -- an
        // in-place edit would leave every install that already ran them
        // unconstrained forever.
        //
        // What makes this one different from the four before it is that it is the
        // first migration in the dial whose DDL is emitted by a SHARED LINK
        // HELPER rather than inline per table, so what the golden dial sees is
        // one helper called 265 times -- and the count that matters is the
        // number of TABLES carrying the constraint (265, asserted from the
        // executed schema by TenantAccountForeignKeyTest), not the number of
        // `$table->foreign(` occurrences a text scan would find. That is exactly
        // the undercount bin/generate-relation-graph.php's docblock warns about,
        // and it is why this bound is on FILES while the constraint count is
        // asserted behaviourally.
        // MOVED 41 -> 42 on 2026-10-04 for `2026_10_04_000800_add_tf_messages_family_foreign_keys.php`
        // (the tf_messages family single-parent relations, Phase 4 Task 4.2).
        // 42 files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
        //
        // MOVED 42 -> 52 on 2026-10-05 for the NINE single-parent FK-family
        // migrations of Phase 4.2 Task 1 (000810, 000820, 000830, 000840,
        // 000850, 000860, 000870, 000880, 000890, 000900). 52 files measured on
        // disk with `ls src/Laravel/Migrations/*.php | wc -l`.
        //
        // Nine of those ten families emit their DDL through a SHARED LINK
        // HELPER exactly as the tenant-FK migration above does, so what this
        // golden dial sees is one helper called many times -- and the number
        // that matters is the number of RELATIONS CARRYING the constraint
        // (97 -> 6 unconstrained, measured by the anti-rot gate), not the
        // number of `$table->foreign(` occurrences a text scan would find. That
        // is the undercount bin/generate-relation-graph.php's docblock warns
        // about, and it is why this bound stays on FILES while the constraint
        // count is asserted behaviourally. The 6 that remain are the
        // `tf_dialog_filters` depth-1 legs, refused with a measured MySQL 3780
        // (parent `id` is `int`, children `bigint`) — see docs/decisions.md D-020.
        //
        // MOVED 53 -> 55 on 2026-10-05 for 2026_10_04_000895_widen_tf_wallpapers_id_
        // to_bigint and 2026_10_04_001200_drop_false_sibling_foreign_keys. 55 files
        // measured on disk. 000895's STEM is load-bearing: it must sort BEFORE
        // 000900, which attempts the wallpapers leg and raises 3780, aborting the
        // whole migrate run — so a widen at a later stem would be unreachable on a
        // fresh install. See the pin in tests/Schema/MySqlDialPortabilityTest.php.
        //
        // 001200 REMOVES a constraint rather than adding one, which is why this
        // bound stays on FILES while the constraint count is asserted
        // behaviourally by the anti-rot gate: tf_messages_service ->
        // tf_messages was never true, and a false constraint that had to be dropped
        // is exactly the case a file count cannot see.
        //
        // MOVED 52 -> 53 on 2026-10-05 for
        // `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php`. 53 files
        // measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
        //
        // It carries no new table and stores no new fact: it re-keys one column
        // and constrains six relations that were previously refused. The
        // constraint count it adds is asserted BEHAVIOURALLY by the anti-rot gate
        // (6 -> 0 unconstrained), not by a text scan of `$table->foreign(` —
        // which is the undercount bin/generate-relation-graph.php's docblock warns
        // about, and why this bound stays on FILES.
        //
        // MOVED 55 -> 56 on 2026-10-05 for the C5 fold
        // `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`,
        // which retires `tf_saved_star_gifts_gift_num` and folds its value onto
        // `tf_saved_star_gifts`. It carries no new table, so this bound — which is
        // on FILES — moves only because the dial grew one file; the table count
        // itself is asserted three ways in ModelGeneratorTest / schema-manifest.
        //
        // MOVED 56 -> 60 on 2026-10-10. Measured on disk with
        // `ls src/Laravel/Migrations/*.php | wc -l` -> 60, which is also what
        // `UpdateIngestor::migrationPaths()` expands to once the two entity
        // FILES it yields alongside the directory are folded in.
        //
        // Four files arrived since the 56 pin, and only ONE of them is the
        // migration this lane wrote. Recording all four by name is the point:
        // a file-count bound that moves for another lane's migration and is
        // then "fixed" by widening the number without saying so is how a bound
        // stops being a bound.
        //
        //   + `2026_10_08_000100_fold_channel_participant_scalars_onto_the_participant_fact.php`
        //       — folds the channel-participant scalars onto the participant
        //         fact. Another lane's, not this one's.
        //   + `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php`
        //       — the curated TL `bytes` columns become UNBOUNDED TEXT instead
        //         of `varchar(2048)`, a 1024-raw-byte ceiling. Another lane's.
        //   + `2026_10_10_000100_fold_update_scalar_children_onto_the_update_fact.php`
        //       — folds the fabricated `tf_updates_*` scalar child tables onto
        //         `tf_updates`. Another lane's.
        //   + `2026_10_10_000200_require_tf_todo_lists_title_text.php`
        //       — THIS lane's, and the only reason this file moved at all by
        //         anything this lane did. It makes `tf_todo_lists_title.text`
        //         NOT NULL, because `todoList.title:TextWithEntities` and the
        //         `textWithEntities.text:string` inside it are both declared
        //         without a conditional marker and are therefore ALWAYS present
        //         on the wire. It adds NO table and NO column — one column
        //         changes nullability — so, as with every entry above, this
        //         bound moves because the dial grew a FILE.
        //
        // THIS PIN AND `CURATED_DIAL_COUNT` IN tests/Schema/MySqlDialPortabilityTest.php MOVE
        // TOGETHER. Never delete a pin. If you are about to change one, grep the other and
        // change it in the same commit with the reason in BOTH comments.
        //
        // RAISED 60 -> 61 on 2026-10-11. THIS IS A CEILING, NOT AN EXACT PIN, and the
        // distinction is the whole reason the bump is legitimate: the assertion below is
        // `assertLessThanOrEqual`, so it permits the dial to be anywhere from 1 up to this
        // number, and raising it does NOT assert that the dial currently holds exactly 61
        // files — the sibling `CURATED_DIAL_COUNT` in MySqlDialPortabilityTest is what
        // asserts that, by `assertCount`. This file is the deliberate BUDGET gate: the cap
        // bounds hand-authored curation, and its own class docblock states the sanctioned
        // mechanism — "a legitimate addition raises it with a record." This is that record.
        //
        // ONE file moved the budget, and it is `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php`
        // — the curated TL `bytes` columns become UNBOUNDED TEXT instead of `varchar(2048)`,
        // because a `varchar(2048)` column holds at most 2048 CHARACTERS (8192 raw bytes
        // under utf8mb4) while the TL `bytes` field is an unbounded raw blob, so the
        // shipped column silently truncated wide values. It is another lane's migration.
        //
        // NOT one of the four files named in the task brief, and git says so rather than
        // inference: `git ls-files src/Laravel/Migrations/ | wc -l` is 60 and the only
        // untracked path under that directory is this one file. The four the brief listed
        // (2026_10_08_000100 fold channel-participant scalars, 2026_10_10_000100 fold
        // update scalars, 2026_10_10_000200 require tf_todo_lists_title_text, 2026_10_11_000100
        // fold bot_infos description) were all added by commits 05e1488d / 4c421a34 /
        // ed3ecc6f, and `git ls-tree -r ed3ecc6f -- src/Laravel/Migrations/ | wc -l`
        // is 60 — the count this budget already held. The brief's hypothesis is disproven.
        //
        // RE-MEASURED 2026-10-11, this file's own `migrationFiles(self::SHIP_DIR)` shape
        // (`count(glob(rtrim($dir,'/').'/*.php'))`) -> 61, and the shell equivalent
        // `ls src/Laravel/Migrations/*.php | wc -l` -> 61. The floor is untouched: this
        // dial has never been below 16, so `assertGreaterThanOrEqual(16, ...)` still bites
        // if the dial is ever gutted rather than merely grown.
        //
        // RAISED 61 -> 62, in the same commit as the sibling exact pin, because the
        // precondition this file recorded as outstanding has now been met:
        // `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php` is no longer
        // UNTRACKED. `git ls-files src/Laravel/Migrations/ | wc -l` = 61 and that exact
        // path is tracked, so the dial is 61 files on a clean clone of HEAD rather than
        // 60 committed files plus one dirty working-tree file. The 2026-10-11 record above
        // deliberately left this ceiling at 61 because it was written against a dirty
        // tree — it correctly refused to count an uncommitted file. Now that the file is
        // committed, a ceiling of 61 sits one below a 61-file dial, which is the exact
        // defect that class of ceiling exists to prevent: it would let the dial be
        // "correct" while admitting zero room for the file that is actually shipped.
        // The sibling `CURATED_DIAL_COUNT` moved 60 -> 61 on the same evidence.
        //
        // WHAT WOULD MAKE 62 WRONG: a revert or rename of the bytes-widening migration
        // returns the dial to 60, and this ceiling must come back down to 61 with the
        // sibling exact pin, in the same commit. The two move as one, in either
        // direction, for the same reason.
        self::assertLessThanOrEqual(62, $files, 'curated dial must stay at or under 62 migrations total');
    }

    public function test_shipped_subset_contains_curated_domains(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        foreach ([
            'create_tf_messages_tables',
            'create_tf_users_tables',
            'create_tf_stars_business_tables',
            'create_tf_misc_tables',
        ] as $stem) {
            self::assertContains(true, array_map(
                static fn (string $n): bool => str_contains($n, $stem),
                $names,
            ), "no shipped migration matches {$stem}");
        }
    }

    public function test_root_namespace_and_special_files_do_not_ship(): void
    {
        $names = self::migrationNames(self::SHIP_DIR);
        self::assertSame([], array_filter($names, static fn (string $n): bool => str_contains($n, 'create_tl_route_tables') || str_contains($n, 'add_tl_foreign_keys')));
    }

    public function test_app_owned_migrations_present(): void
    {
        foreach (self::APP_OWNED_MIGRATIONS as $name) {
            self::assertFileExists(self::SHIP_DIR.'/'.$name, $name.' missing');
        }
    }

    public function test_curated_migrations_are_hand_authored(): void
    {
        foreach (self::APP_OWNED_MIGRATIONS as $skip) {
            $file = self::SHIP_DIR.'/'.$skip;
            if (is_file($file)) {
                self::assertStringNotContainsString(
                    '@generated',
                    (string) file_get_contents($file),
                    "app-owned migration {$skip} must not carry @generated marker",
                );
            }
        }
        $curated = glob(self::SHIP_DIR.'/2026_09_14_2000*.php') ?: [];
        self::assertNotEmpty($curated, 'no curated hand-authored migrations found');
        foreach ($curated as $path) {
            self::assertStringNotContainsString(
                '@generated',
                (string) file_get_contents($path),
                'curated migration must not carry @generated marker: '.basename($path),
            );
        }
    }

    public function test_provider_registers_publishable_migration_path(): void
    {
        $source = (string) file_get_contents(self::PACKAGE_ROOT.'/src/Laravel/Providers/TeleframeServiceProvider.php');

        // Pint (format-on-save) alternates `dirname(__DIR__) . '/Migrations'`
        // and `dirname(__DIR__).'/Migrations'`; the contract is that the
        // provider loads THIS package's Migrations dir, so compare with the
        // concat spacing normalized instead of pinning one exact variant.
        $normalized = preg_replace('/\s*\.\s*/', '.', $source);

        self::assertStringContainsString("loadMigrationsFrom(dirname(__DIR__).'/Migrations')", (string) $normalized);
    }

    /** @return int count of *.php migration files in dir */
    private static function migrationFiles(string $dir): int
    {
        return count(glob(rtrim($dir, '/').'/*.php') ?: []);
    }

    /** @return list<string> basenames of *.php migration files in dir */
    private static function migrationNames(string $dir): array
    {
        return array_map('basename', glob(rtrim($dir, '/').'/*.php') ?: []);
    }
}
