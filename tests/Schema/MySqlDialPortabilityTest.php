<?php

declare(strict_types=1);

namespace MeRezaRezaei\Teleframe\Tests\Schema;

use PHPUnit\Framework\TestCase;

/**
 * Static regression guard for the curated ship dial (src/Laravel/Migrations).
 *
 * Commit 24d7c60f made those 22 hand-authored files actually migrate on
 * MySQL 8.4 by fixing four classes of bugs no PG/SQLite track can ever
 * surface:
 *   1. TEXT NOT NULL DEFAULT ''            -> MySQL error 1101
 *   2. signed account_id vs unsigned id    -> MySQL error 3780
 *   3. auto FK names above 64 chars        -> MySQL error 1059
 *   4. TEXT columns inside a PRIMARY KEY   -> MySQL error 1170
 *
 * Those are untestable against the default (SQLite) run, so this test
 * READS SOURCE only — no schema builder, no database, no connection.
 * PortabilityScanner::violations() scans a migration file as a string and
 * returns every anti-pattern it finds; the negative test proves the
 * scanner can actually DETECT each anti-pattern from an in-memory fake,
 * so this suite never decays into a vacuous green.
 *
 * Regex is used here (PHPUnit scans tests only — phpstan.neon.dist lists
 * `paths: [src]`, and tests/Schema/ShipDialGoldenTest.php already uses
 * preg_replace). The migrations are Pint-formatted and deterministic, so
 * the string-shape assumptions below are audit-able in one glance.
 */
final class MySqlDialPortabilityTest extends TestCase
{
    private const PACKAGE_ROOT = __DIR__.'/../..';

    private const MIGRATION_DIR = self::PACKAGE_ROOT.'/src/Laravel/Migrations';

    /**
     * Exactly 26 files in the curated dial as of 9f71e3dc (cycle-11 backup-store
     * remainder added the tf_store_backup_runs run ledger + the peer_type column
     * on tf_store_buckets):
     *   - 20 hand-authored tf_* domain migrations (incl. tf_store tables,
     *     incl. the tf_accounts runtime account registry, incl. the
     *     backup-store run ledger + peer_type alter)
     *   -  5 app-owned migrations (user_bindings, telegram_apps,
     *     telegram_accounts, add_user_id_to_telegram_accounts,
     *     tg_update_routing)
     *   -  1 cross-domain FK wiring (2026_09_14_299999_create_tf_foreign_keys)
     * ShipDialGoldenTest asserts the same set within 16..28 bounds; this
     * pins the count so a regenerate/curation slip changes the number.
     *
     * Raised 26 -> 27 by Cycle 17 (2026-09-25): the durable backfill cursor
     * ledger, tf_backfill_cursors (2026_09_25_000300), is what lets the "full
     * account history" crawl reach message id 1 across restarts. This pin
     * exists to make a count change LOUD, so a new curated migration must
     * move it deliberately — never be dropped to keep the number still.
     *
     * Raised 27 -> 28 by Version One (2026-09-26): Telegram Passport
     * authentication provenance on tl_user_bindings (passport_fields,
     * passport_verified_at). This pin is the SECOND of two — ShipDialGoldenTest
     * carries the same bound — and it caught the omission, because raising only
     * the golden test's bound left this one red on 28 files. Two pins guarding
     * one number is the correct redundancy: neither is a silent single point.
     */
    // Raised 28 -> 29 by the first-release session-revocation work
    // (2026-09-27): revoked_at on telegram_accounts is the marker that
    // distinguishes "this account needs re-login" from "this account is fine",
    // which nothing could tell before. A durable truth a first-release host
    // needs, so the bound moves WITH the migration rather than the migration
    // being dropped.
    // Raised 29 -> 30 by Cycle 31 (2026-09-29): the nine mirror-catalog tables
    // (tf_reactions, tf_forum_topics, tf_story_items, tf_wallpapers,
    // tf_saved_reaction_tags, tf_message_entities, tf_message_medias,
    // tf_message_actions, tf_dialog_filters) plus their synthetic-key child
    // tables, which the ingest path resolves by name and had never created.
    // (tf_message_entities was among the nine and was DELETED again on
    // 2026-10-04 — it never received a row, and its facts already ship in
    // tf_messages_entities. The count below is unchanged because that deletion
    // edited 2026_09_29_000100 in place; no migration was added or removed, and
    // that file is in no tag, so no install is stranded.)
    // Raised 30 -> 31 (2026-09-29, the rich-message child):
    // 2026_09_29_000200_create_tf_messages_rich_message_table.php. A mirror
    // child SYNTHESIZED by MirrorTableResolver from tf_messages'
    // `["rich_message","FK→RichMessage"]` entry rather than a catalog JSON
    // key, so MirrorCatalogShipsInMigrationsTest's name-set assertion could
    // not see it, while decomposeChildren() writes a row for it on the first
    // `message` carrying rich_message:flags2.13 and no migration had created
    // the table — no-such-table on a host whose migrate returned green.
    // Raised 31 -> 32 (2026-09-29, the search index):
    // 2026_09_29_000300_create_tf_message_search_table.php. tf_message_search
    // is the inverted index behind MessageSearchQuery, which until then ran a
    // leading-wildcard LIKE that no index can serve — a full scan of the whole
    // message history on every search. A new TABLE was required (not a new
    // index on tf_messages) because a B-tree cannot serve a leading '%'.
    // Raised 32 -> 33 (2026-09-29, the channel access-hash store):
    // 2026_09_29_000400_create_tf_chats_access_hash_table.php. A REAL
    // catalogued table, added for the same reason 30 -> 31 was: a child that
    // MirrorTableResolver SYNTHESIZES from a catalog entry but which no
    // migration created, so the ingest path wrote into a table that did not
    // exist on a host whose `php artisan migrate` had returned green.
    // `tf_chats_access_hash` is synthesized from `tf_chats`' new child entry
    // `["access_hash","BIGINT NOT NULL"]` — the flag-gated union fact
    // `channel`/`channelForbidden` carry and `chatEmpty`/`chat`/`chatForbidden`
    // do not (schema/sources/api.tl:102-105), mirroring `tf_users_access_hash`
    // exactly. Without it `BackfillCommand::mirrorPeerLookup()` had only a
    // `tf_channels` table to read for a negative peer id, and `tf_channels` is
    // NOT a catalog table and is written by nothing — so every real
    // `--peer=<channel>` was refused with `peer_without_access_hash` even after
    // a live `--discover` had stored 157 chats. The bound moves WITH the
    // migration, which is what this pin exists to force into the open; it is
    // never dropped to keep the number still.
    //
    // None of the four anti-patterns apply to it, by construction:
    //   - MySQL-1101: it has no TEXT column at all.
    //   - MySQL-3780: account_id is unsignedBigInteger.
    //   - MySQL-1059: it declares no ->foreign() at all, so no auto FK name
    //     can be generated, let alone exceed 64 chars.
    //   - MySQL-1170: the indexed column is string('token', 255), NOT text —
    //     which is the whole reason it is 255 and not text, since a TEXT column
    //     cannot be part of a MySQL index key.
    //
    // Raised 33 -> 34 (2026-10-02, tf_ingest_clues — the mirror's coverage
    // accounting). The bound is a ratchet, not a freeze: a new durable truth
    // table legitimately moves it. Raised WITH its migration, never by dropping
    // a pin. See ShipDialGoldenTest for the sanctioned mechanism.
    // 2026_10_02_000500_create_tf_ingest_clues_table.php is the ledger of what
    // the mirror FAILED to store — the four clue buckets were computed and
    // discarded by TWO layers (measured, not guessed:
    // `MirrorIngesterFactory::build()`'s `ingest()` call and
    // `MirrorIngesterFactory::ingestNestedSources()`), so no coverage gap was
    // recorded anywhere. It is
    // LOCAL/APP state, not a mirror fact, so none of the four anti-patterns
    // apply: it declares no ->foreign() at all (so no auto FK name exists to
    // exceed 64 chars, MySQL-1059); its account_id is unsignedBigInteger
    // (MySQL-3780); its only TEXT column, `detail`, is NOT in any index, and
    // its two indexes are (account_id, bucket) and (account_id, seen_at) over
    // an 8-byte integer and a 32-char string (MySQL-1170); and no TEXT column
    // carries a default at all (MySQL-1101).
    // Raised 34 -> 35 (2026-10-04, the tenant-key parent fix):
    // 2026_10_04_000100_make_user_id_unsigned_unique.php makes
    // telegram_accounts.user_id UNSIGNED and UNIQUE, which is what the 265
    // `tf_*.account_id` tenant foreign keys need before any of them can be
    // created: as shipped the parent is a signed BIGINT against unsigned
    // children, and MySQL 8.4.11 answers ERROR 3780 (measured; docs/decisions.md
    // D-005, candidate A). It is a schema-shape migration on ONE app table, not
    // a mirror fact, so none of the four anti-patterns apply to it: it declares
    // no ->foreign() at all, so no auto FK name exists to exceed 64 chars
    // (MySQL-1059); it has no TEXT column, so there is no bare default
    // (MySQL-1101); it indexes only an 8-byte integer (MySQL-1170); and its
    // column is `user_id`, not `account_id`, so MySQL-3780 does not apply to it
    // -- it is the change that RESOLVES MySQL-3780 one level up, on the parent.
    // It is also the first type-altering migration in the repo (->change()
    // appeared zero times in src/, tests/ and bin/ before it), which is why the
    // pin moves WITH it rather than being a routine ratchet bump.
    // Raised 35 -> 36 (2026-10-04, the six retargeted tenant foreign keys):
    // 2026_10_04_000200_retarget_tenant_foreign_keys.php re-points six shipped
    // constraints -- tf_updates, tf_update_routing, tf_update_state,
    // tf_channel_participants, tf_channel_updates, tf_update_differences --
    // from `account_id -> telegram_accounts.id` to
    // `account_id -> telegram_accounts.user_id`, dropping and re-adding each
    // with its ON DELETE CASCADE preserved. The engine writes the Telegram user
    // id (src/Laravel/MirrorIngesterFactory.php:160), so as shipped those six
    // REFUSED every real ingest row on any enforcing connection; measured
    // ERROR 1452 on MySQL 8.4.11 before, 6/6 landing after (docs/decisions.md
    // D-005, "Blocker 2", disposition RETARGET rather than DROP).
    // It creates NO table and adds no fact, so this is not a ratchet bump for a
    // new durable truth table -- it is a CORRECTNESS fix to six existing ones,
    // and it lands in a NEW file precisely because editing the two create
    // migrations would strand every install that already ran them. On this
    // test's own four anti-patterns: it declares ->foreign() with an EXPLICIT
    // name, every one of which is `{table}_tenant_fk` -- 33 characters at the
    // longest (`tf_channel_participants_tenant_fk`, measured), so none
    // approaches the 64-character MySQL identifier ceiling (MySQL-1059); it adds
    // no column and
    // no TEXT, so bare defaults and TEXT indexing cannot arise (MySQL-1101,
    // MySQL-1170); and its child column is the already-unsigned `account_id`
    // against a parent that the 000100 migration made unsigned, so MySQL-3780
    // does not apply -- resolving it is the whole point of this migration.
    //
    // Raised 36 -> 39 (2026-10-04, the D-007 column contract): THREE files, one
    // per family group, adding the nine columns `MirrorTableResolver` emits and
    // the shipped DDL never declared.
    //   - 2026_10_04_000300_add_recent_repliers_to_tf_messages_replies.php
    //     (tf_messages_replies.recent_repliers_type / .recent_repliers_id)
    //   - 2026_10_04_000400_add_star_gift_host_and_availability_columns.php
    //     (availability_issued, host_id_type, host_id_id on BOTH
    //      tf_stars_transactions_stargift and tf_saved_star_gifts_gift -- two
    //      tables, one file, because both flatten the same StarGift union
    //      through the same private starGiftColumns() helper, so it is one
    //      omission and not two)
    //   - 2026_10_04_000500_add_text_column_to_tf_todo_lists_title.php
    //     (tf_todo_lists_title.text)
    //
    // This is the ONE kind of migration for which this pin's usual reasoning
    // ("a new durable truth table legitimately moves a ratchet") does NOT
    // apply, and saying so is the point: none of the three creates a table,
    // adds a fact, or stores anything new. They close a CONTRACT breach -- the
    // shipped column set is a contract with MirrorTableResolver's output, which
    // nothing enforced until ResolvedColumnsMatchShippedDdlTest (docs/decisions.md
    // D-007). Each was silently dropping rows TWICE: MirrorFactWriter::write()
    // strips a column the live table lacks, and the row is then declined by
    // whichever sibling is NOT NULL with no default, leaving only a clue string.
    // So the bound moves WITH the migrations rather than the migrations being
    // squeezed out to keep the number still -- a pin that made the fix
    // impossible would have been the defect.
    //
    // On this test's own four anti-patterns, all three are column-only ADDs and
    // none of the four can arise:
    //   - MySQL-1101: 000300 and 000400 add no TEXT column at all. 000500 adds
    //     one TEXT column (`text`) with NO default at all, which is the only
    //     shape that is safe twice over -- the create migrations spell their
    //     TEXT defaults ->default(DB::raw("('')")) precisely because a bare
    //     literal default is what this rule forbids, and 000500 needs no default
    //     because it is nullable (see its own docblock for why nullable rather
    //     than NOT NULL: an ALTER on a possibly-populated table is refused by
    //     MySQL strict mode with ERROR 1364).
    //   - MySQL-3780: none of the three declares a column named `account_id`;
    //     all three leave the shipped key untouched.
    //   - MySQL-1059: none of the three declares ->foreign() at all, so neither
    //     an implicit nor an explicit constraint name is generated.
    //   - MySQL-1170: none of the three adds an index, and each table's shipped
    //     PRIMARY KEY stays exactly as it was -- integral columns only, no TEXT
    //     enters any index key.
    //
    // Raised 39 -> 40 (2026-10-04, the sixteen `tf_saved_star_gifts_*` keys):
    // 2026_10_04_000600_retype_saved_star_gift_key_to_bigint.php narrows the
    // single key column all sixteen of those child tables share from the
    // `string($key, 255)` that `factKey()` declared
    // (2026_09_14_200040_create_tf_stars_business_tables.php:559-563) to
    // `bigInteger`, because the key IS the StarGift union's `id` and BOTH arms
    // declare it `id:long` (api_full.tl:1974 and :1975). It is a NEW file
    // because 200040 is SHIPPED -- present at tags v1.3.0, v1.3.1 and v1.3.2 --
    // so editing the create migration in place would strand every install that
    // already ran it.
    //
    // It creates no table and adds no fact, so this pin's "a new durable truth
    // table legitimately moves a ratchet" reasoning does not apply either: it
    // closes the same CONTRACT breach the three above closed, which
    // ResolvedColumnsMatchShippedDdlTest measured as all sixteen reporting
    // "resolver emits bigint (BIGINT), shipped DDL declares text". Unlike
    // those three it also cannot be argued as a column-only ADD, because here
    // the SHIPPED column is the one that was wrong and the DDL changes shape --
    // which is why the bound moves WITH the migration rather than the migration
    // being squeezed out to keep the number still.
    //
    // On this test's own four anti-patterns, all four are structurally
    // unreachable, and the third one is worth stating explicitly because a
    // PK-column TYPE change is exactly what rule 4 polices:
    //   - MySQL-1101: the file declares no TEXT column at all.
    //   - MySQL-3780: the altered column is named `id`, never the tenant leg.
    //   - MySQL-1059: IT declares no constraint, so no implicit or explicit
    //     name is generated.
    //
    // The 41st file is `2026_10_04_000700_add_tenant_foreign_keys_to_mirror_tables.php`
    // (the 265 tenant FKs -> telegram_accounts.user_id), and rule 3
    // (MySQL-1059, identifier length) is the one rule that migration genuinely
    // walks into, so the reasoning belongs here rather than only in the pin:
    //   - the constraints are created inside `Schema::table`, so rule 4's
    //     primary-key sub-rules do not apply -- it introduces, removes and
    //     rewrites no primary key;
    //   - MySQL's identifier cap is 64 characters and it was MEASURED one
    //     character at a time on a scratch database: 64 accepted, 65 refused
    //     with `ERROR 1059 ... Identifier name is too long`. The longest tf_*
    //     table name is 49 (`tf_stars_transactions_starref_commission_permille`),
    //     leaving a 15-character budget, and the suffix `_acc_tenant_fk` is 14
    //     -- 63 total, one character inside. An earlier, clearer-looking
    //     `_account_tenant_fk` (18) was 3 over and was REFUSED BY THE FIRST REAL
    //     ALTER on MySQL, while sqlite, which has no such limit, accepted all
    //     259 without complaint. The suffix is therefore pinned by
    //     TenantAccountForeignKeyTest's budget proof, not trusted here;
    //   - MySQL-3780 is unreachable for a fourth, independent reason: the
    //     migration asserts the parent's UNIQUE index and refuses without it,
    //     and that index exists only because 2026_10_04_000100 made `user_id`
    //     BIGINT UNSIGNED -- a signed parent and an unsigned `account_id`
    //     cannot be constrained at all.
    //   - MySQL-1170: this is the one that could have bitten, and it cannot.
    //     All sixteen columns ARE in a primary key (fifteen as (account_id, id),
    //     tf_saved_star_gifts_collection_id as (account_id, id, position), the
    //     latter with a secondary index on (account_id, id)), so all seventeen
    //     index entries change shape. Rule 4 reads column types and PRIMARY KEY
    //     columns only inside a `Schema::create` block ($inCreateBlock, lines
    //     387 and 397-404) and this migration uses `Schema::table` throughout,
    //     so it introduces, removes and rewrites no primary key at all. Even if
    //     it were scanned, both sub-rules move the right way: rule (a) forbids
    //     TEXT in a PK, rule (b) polices the length of a PK column declared as a
    //     string, and these columns stop being declared as either -- on InnoDB
    //     with utf8mb4 the change also shrinks each key from up to 1020 bytes to
    //     8, moving away from the index-length ceiling rather than toward it.
    //
    // It moves 40 -> 41 for the tenant-FK migration above. The count is a
    // ratchet on the DIAL, not a ceiling on effort: it exists so that a file
    // appearing or vanishing is a deliberate, reviewed act. 41 files are on
    // disk, measured with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    // MOVED 41 -> 42 on 2026-10-04 for `2026_10_04_000800_add_tf_messages_family_foreign_keys.php`
    // (the tf_messages family single-parent relations, Phase 4 Task 4.2). 42
    // files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    // MOVED 42 -> 52 on 2026-10-05 for the NINE single-parent FK-family
    // migrations of Phase 4.2 Task 1, added after the 42 baseline:
    //   000810 tf_stars_transactions   000840 tf_saved_star_gifts
    //   000820 tf_users                 000850 tf_bot_*   (5 parents)
    //   000830 tf_channels              000860 tf_channel_participants + _updates
    //   000870 tf_dialog_filters        000890 tf_stars_subscriptions
    //   000880 tf_chats                 000900 update_differences + misc
    // 52 files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    // What the ten families bought, MEASURED by the anti-rot gate
    // (tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php): the
    // count of single-parent relations declared by the schema, mechanically
    // FK-able, and carrying NO constraint fell 97 -> 6. The surviving 6 are
    // all `tf_dialog_filters_* -> tf_dialog_filters`, refused for a MECHANICAL
    // reason measured on real MySQL 8.4.11: the parent's `id` is `int` and the
    // children's is `bigint`, so the FOREIGN KEY is refused with ERROR 3780.
    // See docs/decisions.md D-020 — the refusal is deliberately UNCONDITIONAL
    // (not driver-gated), because gating it would give SQLite a schema MySQL
    // does not have, and a green SQLite suite would then describe constraints
    // that do not exist on the truth-track engine.
    //
    // This pin AND `assertLessThanOrEqual` IN tests/Schema/ShipDialGoldenTest.php
    // MOVE TOGETHER. Never delete a pin. If you are about to change one, grep
    // the other and change it in the same commit with the reason in BOTH comments.
    // MOVED 53 -> 55 on 2026-10-05 for TWO migrations, and the second one is
    // the reason the dial works on MySQL at all:
    //   2026_10_04_000895_widen_tf_wallpapers_id_to_bigint  (the 3780 fix)
    //   2026_10_04_001200_drop_false_sibling_foreign_keys    (removes a FALSE FK)
    // 55 files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    // 000895's STEM IS LOAD-BEARING, and this pin is where that gets recorded:
    // 000900 does NOT refuse the wallpapers leg (its REFUSED is []), so it
    // ATTEMPTS it, raises MySQL 3780, the migrate run ABORTS, and no later file
    // executes. A widen at a late stem (001300) would therefore be unreachable on
    // a fresh install — correct in review, green on every SQLite test, and fixing
    // nothing on the engine that matters. 000895 sorts at index 51, before 000900
    // at 52. Verified in-dial.
    //
    // WHY IT WAS NEEDED AT ALL: tf_wallpapers.id is integer() and
    // tf_wallpapers_settings.id is bigInteger() — fourteen lines apart in the SAME
    // shipped file (2026_09_29_000100:163 and :177). Both legs are SIGNED and it
    // still fails: a MySQL FOREIGN KEY needs signedness AND integer width to agree.
    // SQLite's grammar returns 'integer' for both, so the pair is indistinguishable
    // there and the defect is structurally invisible to every SQLite gate.
    //
    // WHAT IT BOUGHT, measured on real MySQL 8.4.11: the dial applies (55 files,
    // 271 tables, 476 FKs over 794 legs, foreign_key_checks=1) and tests/MySql goes
    // from 'cannot migrate' to 60 tests / 2236 assertions / 2 failures.
    //
    // MOVED 52 -> 53 on 2026-10-05 for
    // `2026_10_04_001100_widen_tf_dialog_filters_id_to_bigint.php`, which widens
    // tf_dialog_filters.id from int to bigint and thereby makes the family's six
    // depth-1 legs CONSTRAINABLE — the escape clause docs/decisions.md D-020 wrote
    // in advance: "If tf_dialog_filters.id is ever widened to bigint by a future
    // migration, the 6 refusals become constrable with no key change and must be
    // landed then."
    //
    // The widening is safe in the direction taken: the PARENT was the narrow one
    // and every child was ALREADY bigint, so widening moves the parent TOWARD its
    // children and no row is stranded. The hazard D-020 warned about — a widened
    // parent with UNWIDENED children resolving to no parent — does not apply,
    // because there is no unwidened child. Measured: zero columns anywhere
    // reference tf_dialog_filters.id other than the six children, and all six are
    // bigint.
    //
    // WHAT IT BOUGHT, measured by the anti-rot gate
    // (tests/Schema/Mirror/EveryFkableRelationHasAConstraintTest.php): the count
    // of single-parent relations that are mechanically FK-able and carry NO
    // constraint fell 6 -> 0. That gate is now GREEN: every FK-able relation in
    // the mirror carries a real constraint.
    //
    // 55 files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    // MOVED 55 -> 56 on 2026-10-05 for audit row C5, the fold
    // `2026_10_05_000100_fold_saved_star_gift_num_onto_the_saved_gift_fact.php`.
    // That file carries NO new table — it retires one — which is why the count
    // that matters is not this one: the mirror's own table count is asserted
    // three ways elsewhere (264 in `mirror-shape.json`, in
    // `schema-manifest.json`'s `mirror_tables`/`models`, and in the model tree
    // itself), and `tests/Schema/ModelGeneratorTest.php` proves the bijection.
    // This pin is on FILES, so it moves only because the dial grew a file.
    //
    // 56 files measured on disk with `ls src/Laravel/Migrations/*.php | wc -l`.
    //
    /**
     * Pinned count of curated dial migrations.
     *
     * CONTENDED BY CONCURRENT LANES: this constant moved 56 → 58 → 59 → 60 within a single
     * session, tracking migrations added by other lanes rather than by a schema change
     * made here. The four substantive rules this class enforces (MySQL-1101 bare TEXT
     * default, 1170 TEXT-in-index, 3780 signed FK legs, 1059 identifier length) are the
     * real gate and all pass independently of this number. Whoever adds a migration MUST
     * bump this in the same change; a red pin means "a migration was added without
     * updating the pin", not "the dial is broken".
     *
     * MOVED 59 -> 60 on 2026-10-10 for audit row C4, the nullability fix
     * `2026_10_10_000200_require_tf_todo_lists_title_text.php` — which makes
     * `tf_todo_lists_title.text` NOT NULL because the TL declares the field
     * always present. It adds NO table and NO column, so this pin moves for the
     * same reason it always moves: it is a pin on FILES, and the dial grew one.
     * Measured, not assumed:
     *
     *     MEASURED 2026-10-10, `UpdateIngestor::migrationPaths()` expanded the
     *     same way `MySqlTestCase::mysqlMigrationFiles()` expands it (directory
     *     -> sorted glob, plain file -> itself, deduped by realpath):
     *       -> 60
     *
     * MOVED 60 -> 61 on 2026-10-11. ONE file, and it is NOT one of the four
     * named in the task brief — all four of those were ALREADY inside the 60
     * this constant was authored against, and git provenance proves it (see
     * the attribution below). The single file that took the dial from 60 to 61
     * is:
     *
     *   + `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php`
     *       — the curated TL `bytes` columns become UNBOUNDED TEXT instead of
     *         `varchar(2048)`. `varchar(2048)` under utf8mb4 is a 2048-CHARACTER
     *         ceiling of at most 8192 raw bytes, and the TL `bytes` field is a
     *         raw byte blob with no such bound, so the shipped column silently
     *         truncated any bytes value wider than that. Another lane's
     *         migration, not this lane's — recorded by name precisely so the
     *         number is never moved again without saying what moved it.
     *
     * ATTRIBUTION, established with git rather than assumed. `git ls-files
     * src/Laravel/Migrations/ | wc -l` = 60, and
     * `git ls-files --others --exclude-standard src/Laravel/Migrations/`
     * names exactly one untracked file, the one above. The four migrations the
     * brief listed were each added by a commit that is already an ancestor of
     * this pin:
     *   05e1488d 2026-10-10  2026_10_08_000100_fold_channel_participant_scalars...
     *   05e1488d 2026-10-10  2026_10_10_000100_fold_update_scalar_children...
     *   4c421a34 2026-10-11  2026_10_10_000200_require_tf_todo_lists_title_text...
     *   ed3ecc6f 2026-10-11  2026_10_11_000100_fold_bot_infos_description...
     * and `git ls-tree -r ed3ecc6f -- src/Laravel/Migrations/ | wc -l` = 60,
     * the same count this constant already held. So the brief's hypothesis —
     * that one of those four pushed the dial 60 -> 61 — is DISPROVEN. Four of
     * the five named files are red herrings; only the untracked fifth is real.
     *
     * RE-MEASURED 2026-10-11 by three independent methods, all agreeing:
     *   `php -r 'echo count(glob("src/Laravel/Migrations/*.php"))'`      -> 61
     *   `ls src/Laravel/Migrations/*.php | wc -l`                       -> 61
     *   `UpdateIngestor::migrationPaths()` expanded + realpath-deduped  -> 61
     *
     * WHAT WOULD MAKE THIS WRONG, stated so a later reader can tell a settled
     * pin from a stale one: the 61 written here counted
     * `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php`, a file
     * belonging to a concurrent lane and NOT committed. It was measured in the
     * working tree, where that file was present, and then committed — so the
     * pin shipped describing a dial no checkout of this commit contains. That is
     * the same defect as a generated artifact measured on a dirty tree: it
     * passed locally and failed on a clean clone.
     *
     * CORRECTED to 60 on a clean clone of HEAD, where
     * `git ls-files src/Laravel/Migrations/ | wc -l` = 60. The migration above
     * is a real fix and still deserves committing, but it is NOT part of this
     * commit: **if and when it is committed, this constant goes to 61 in that
     * same commit.** A pin and the file set it counts must move as one — in
     * either direction.
     *
     * RAISED 60 -> 61, and the precondition above has now actually fired:
     * `2026_10_08_000200_widen_bytes_columns_to_unbounded_text.php` is no
     * longer untracked. `git ls-files src/Laravel/Migrations/ | wc -l` = 61 and
     * that path is tracked, so a clean clone of HEAD carries 61 migrations and
     * the old pin failed on CI with "curated dial count drifted from 60 files"
     * / "actual size 61 matches expected size 60" at this file's line 462. The
     * correction described above was therefore not a judgement about whether
     * the 61st file was worth shipping — it never was — only about the pin and
     * the file set moving together. They now move together, in the same commit,
     * with the sibling ceiling in tests/Schema/ShipDialGoldenTest.php raised
     * 61 -> 62 for the same reason.
     *
     * WHAT WOULD MAKE THE 61 WRONG: this is an EXACT `assertCount`, so it is
     * correct only while exactly 61 migration files exist. Any further curated
     * addition or removal — including a revert of the bytes-widening migration
     * above — changes the number and must move this constant in the same
     * commit, in whichever direction the dial moved.
     */
    private const CURATED_DIAL_COUNT = 62;

    public function test_curated_dial_count_is_pinned(): void
    {
        $files = glob(self::MIGRATION_DIR.'/*.php') ?: [];
        self::assertCount(
            self::CURATED_DIAL_COUNT,
            $files,
            'curated dial count drifted from '.self::CURATED_DIAL_COUNT.' files',
        );
    }

    public function test_no_bare_text_defaults(): void
    {
        self::assertNoRuleViolations('MySQL-1101', 'bare TEXT default (must be DB::raw expression or absent)');
    }

    public function test_account_id_columns_are_unsigned(): void
    {
        self::assertNoRuleViolations('MySQL-3780', 'signed account_id (must be unsignedBigInteger)');
    }

    public function test_foreign_key_names_stay_within_64_chars(): void
    {
        self::assertNoRuleViolations('MySQL-1059', 'FK constraint name over 64 chars');
    }

    public function test_no_text_column_sits_in_a_primary_key(): void
    {
        self::assertNoRuleViolations('MySQL-1170', 'TEXT column used in a PRIMARY KEY');
    }

    public function test_scanner_flags_every_antipattern_in_a_fake_dial(): void
    {
        $violations = PortabilityScanner::violations(self::contaminatedDial());
        $joined = implode("\n", $violations);

        self::assertStringContainsString('MySQL-1101', $joined, 'bare TEXT default was not flagged');
        self::assertStringContainsString('MySQL-3780', $joined, 'signed account_id was not flagged');
        self::assertStringContainsString('MySQL-1059', $joined, 'over-long FK name was not flagged');
        self::assertStringContainsString('MySQL-1170', $joined, 'TEXT column in PRIMARY KEY was not flagged');

        // MySQL-1059 must read the second-argument spelling of an explicit
        // name (foreign($columns, $name)) in BOTH directions. Recognising the
        // form is only half the fix; failing to recognise it produced a false
        // positive on a correct shipped migration, so that exact case is
        // pinned here and would otherwise rot back in silence.
        self::assertStringContainsString(
            'fk_second_argument_explicit_name_over_sixty_four_chars_threshold_abcdefghijklmnop',
            $joined,
            'over-long SECOND-ARGUMENT foreign() name was not flagged',
        );
        self::assertStringNotContainsString(
            'fk_tfrl_overflow_named',
            $joined,
            'short SECOND-ARGUMENT foreign() name was falsely flagged',
        );

        // The absence above is also load-bearing as a COUNT: the scanner
        // that did not understand the second argument emitted FOUR MySQL-1059
        // violations for this dial instead of three, the extra one being the
        // 22-char name above reported as a 101-char 'auto' name. Asserting the
        // number keeps the false positive from returning silently, since its
        // message names the invented auto name, not the real one.
        $identifierViolations = array_values(array_filter(
            $violations,
            static fn (string $message): bool => str_contains($message, 'MySQL-1059'),
        ));
        self::assertCount(
            3,
            $identifierViolations,
            "expected exactly 3 over-long FK names in the fake dial:\n".implode("\n", $identifierViolations),
        );
    }

    /**
     * Feed every real migration through the scanner and fail with the full
     * violation list if any rule trips.
     */
    private static function assertNoRuleViolations(string $marker, string $what): void
    {
        $files = glob(self::MIGRATION_DIR.'/*.php') ?: [];
        self::assertNotEmpty($files);

        $failures = [];
        foreach ($files as $path) {
            foreach (PortabilityScanner::violations((string) file_get_contents($path)) as $message) {
                if (str_contains($message, $marker)) {
                    $failures[] = basename($path).': '.$message;
                }
            }
        }

        self::assertSame([], $failures, "{$what}: \n".implode("\n", $failures));
    }

    /**
     * In-memory fake dial carrying all four anti-patterns. Pure string —
     * never touches src/Laravel/Migrations.
     */
    private static function contaminatedDial(): string
    {
        return <<<'PHP'
            <?php

            return new class extends Migration
            {
                public function up(): void
                {
                    Schema::create('tf_contaminated', function (Blueprint $table) {
                        $table->bigInteger('account_id');                 // MySQL-3780: signed
                        $table->integer('id');
                        $table->text('note')->default('');                // MySQL-1101: plain literal default
                        $table->text('body');                             // MySQL-1170: TEXT inside PK
                        $table->primary(['account_id', 'id', 'note', 'body']);
                    });

                    Schema::create('tf_really_long_table_name_that_would_overflow', function (Blueprint $table) {
                        $table->unsignedBigInteger('account_id');
                        $table->integer('id');
                        $table->foreign(['account_id', 'id', 'position', 'seq', 'flag'])
                            ->references(['account_id', 'id']);           // MySQL-1059: auto name way past 64

                        // Same columns, but the name is the SECOND argument of
                        // foreign(). The auto name above is 85 chars; this
                        // constraint is 22. It must NOT be flagged — this is
                        // the exact false positive that made the shipped gate
                        // reject a correct migration, so it is pinned in both
                        // directions alongside the positive case below.
                        $table->foreign(['account_id', 'id', 'position', 'seq', 'flag'], 'fk_tfrl_overflow_named')
                            ->references(['account_id', 'id']);
                    });

                    Schema::table('tf_explicit', function (Blueprint $table) {
                        $table->foreign(['account_id', 'id'])
                            ->references(['account_id', 'id'])
                            ->on('parents')
                            ->name('fk_really_long_explicit_name_over_sixty_four_chars_threshold_abcdefghijklmnopqrstuvwxyz');

                        // MySQL-1059 via the SECOND-argument spelling: the rule
                        // must still fire when the explicit name itself is
                        // over 64 chars, or teaching the scanner the form
                        // would have silently disarmed it.
                        $table->foreign(['account_id', 'id'], 'fk_second_argument_explicit_name_over_sixty_four_chars_threshold_abcdefghijklmnop')
                            ->references(['account_id', 'id'])
                            ->on('parents');
                    });
                }
            };
            PHP;
    }
}

/**
 * Line-scoped source scanner for the curated dial's MySQL portability
 * invariants. Operates on raw migration text only.
 */
final class PortabilityScanner
{
    private const MYSQL_IDENTIFIER_LIMIT = 64;

    /** @return list<string> human-readable violation messages. */
    public static function violations(string $source): array
    {
        $violations = [];
        $lines = explode("\n", $source);

        $currentTable = null;
        $inCreateBlock = false;
        $blockColTypes = [];
        $blockStringLen = [];
        $blockPrimary = [];

        foreach ($lines as $i => $line) {
            $stmt = trim($line);
            $no = $i + 1;

            // --- table context: Schema::create('X' / Schema::table('X' ---
            if (preg_match("/Schema::(create|table)\(\s*'([^']+)'/", $line, $m) === 1) {
                $currentTable = $m[2];
                $inCreateBlock = $m[1] === 'create';
                $blockColTypes = [];
                $blockStringLen = [];
                $blockPrimary = [];

                continue;
            }

            // --- rule 4: flush PRIMARY KEY of the create block on `});` ---
            if ($currentTable !== null && $inCreateBlock && $stmt === '});') {
                foreach ($blockPrimary as $col) {
                    if (($blockColTypes[$col] ?? null) === 'text') {
                        $violations[] = self::at($no, "[MySQL-1170] TEXT column '{$col}' sits in the PRIMARY KEY of '{$currentTable}' — string-id PKs must be string('id', 255)");
                    }
                    if (($blockColTypes[$col] ?? null) === 'string' && ($blockStringLen[$col] ?? 255) !== 255) {
                        $violations[] = self::at($no, "[MySQL-1170] string PK column '{$col}' of '{$currentTable}' must be string('id', 255) (got length {$blockStringLen[$col]})");
                    }
                }
                $inCreateBlock = false;
                $blockColTypes = [];
                $blockStringLen = [];
                $blockPrimary = [];
            }

            if ($currentTable === null) {
                continue;
            }

            // --- rule 1: TEXT with a non-expression default -----------------
            if (str_contains($stmt, '->text(') && str_contains($stmt, '->default(')) {
                $defaultSegment = substr($stmt, strpos($stmt, '->default('));
                if (! str_contains($defaultSegment, 'DB::raw(')) {
                    $violations[] = self::at($no, "[MySQL-1101]  TEXT column default must be DB::raw(''), found plain literal: {$stmt}");
                }
            }

            // --- rule 2: signed account_id ----------------------------------
            if (str_contains(strtolower($stmt), "biginteger('account_id'") && ! str_contains(strtolower($stmt), "unsignedbiginteger('account_id'")) {
                $violations[] = self::at($no, "[MySQL-3780] account_id must be unsignedBigInteger to match telegram_accounts.id: {$stmt}");
            }

            // --- rule 3: FK constraint names within 64 chars ----------------
            if (str_contains($stmt, '->foreign(')) {
                self::foreignKeyViolations($lines, $i, $currentTable, $violations);
            }

            // --- rule 4: collect column types + PK columns of this block ----
            if ($inCreateBlock) {
                if (preg_match("/->text\('([^']+)'/", $stmt, $m) === 1) {
                    $blockColTypes[$m[1]] = 'text';
                }
                if (preg_match("/->string\('([^']+)'(?:,\s*(\d+))?/", $stmt, $m) === 1) {
                    $blockColTypes[$m[1]] = 'string';
                    $blockStringLen[$m[1]] = (int) ($m[2] ?? 255);
                }
                if (preg_match('/->primary\(\[([^\]]*)\]\)/', $stmt, $m) === 1) {
                    preg_match_all("/'([^']+)'/", $m[1], $cols);
                    $blockPrimary = array_merge($blockPrimary, $cols[1]);
                }
            }
        }

        return $violations;
    }

    /** @param list<string> $lines @param list<string> $out */
    private static function foreignKeyViolations(array $lines, int $fkLine, string $currentTable, array &$out): void
    {
        $stmt = '';
        for ($j = $fkLine, $n = count($lines); $j < $n; $j++) {
            $stmt .= trim($lines[$j]);
            if (str_contains($lines[$j], ';')) {
                break;
            }
        }

        $columns = self::foreignKeyColumns($lines[$fkLine]);
        if ($columns === []) {
            return;
        }

        // An explicit name may be spelled two legal ways, and a name passed
        // in EITHER form means no auto name is generated — so both must be
        // collected here, or a correctly-named constraint falls through to
        // the auto-name branch below and is flagged for a name MySQL never
        // sees.
        $rawNames = [];
        $chained = self::nameArgument($stmt);
        if ($chained !== null) {
            $rawNames[] = $chained;
        }
        $secondArgument = self::foreignSecondArgument($stmt);
        if ($secondArgument !== null) {
            $rawNames[] = $secondArgument;
        }

        if ($rawNames !== []) {
            foreach ($rawNames as $raw) {
                $explicit = self::evaluateNameArgument($raw, $currentTable);
                if ($explicit !== null && strlen($explicit) > self::MYSQL_IDENTIFIER_LIMIT) {
                    $out[] = self::at($fkLine + 1, "[MySQL-1059] explicit FK name '{$explicit}' exceeds ".self::MYSQL_IDENTIFIER_LIMIT.' chars');
                }
            }

            return;
        }

        $autoName = $currentTable.'_'.implode('_', $columns).'_foreign';
        if (strlen($autoName) > self::MYSQL_IDENTIFIER_LIMIT) {
            $out[] = self::at($fkLine + 1, "[MySQL-1059] auto FK name '{$autoName}' exceeds ".self::MYSQL_IDENTIFIER_LIMIT.' chars — add ->name(...)');
        }
    }

    /** @return list<string> column names referenced by a ->foreign(...) line. */
    private static function foreignKeyColumns(string $line): array
    {
        $pos = strpos($line, '->foreign(');
        if ($pos === false) {
            return [];
        }

        $inner = self::parenArgument($line, $pos + strlen('->foreign('));
        if ($inner === null) {
            return [];
        }

        // Only the FIRST argument is the column list. Reading past it would
        // fold a trailing constraint name into the columns and corrupt the
        // auto-name the rule computes.
        $parts = self::splitTopLevelArguments($inner);
        $inner = trim($parts[0] ?? '');

        if ($inner === '') {
            return [];
        }

        if ($inner[0] !== '[') {
            if (preg_match("/^'([^']+)'/", $inner, $m) === 1) {
                return [$m[1]];
            }

            return [];
        }

        preg_match_all("/'([^']+)'/", $inner, $m);

        return $m[1];
    }

    /** @return string|null the raw argument of ->name(...), or null when absent. */
    private static function nameArgument(string $stmt): ?string
    {
        $pos = strpos($stmt, '->name(');
        if ($pos === false) {
            return null;
        }

        return self::parenArgument($stmt, $pos + strlen('->name('));
    }

    /**
     * The SECOND legal spelling of an explicit FK name: the second argument
     * of foreign() itself — `foreign(['a','b'], 'fk_x')` and
     * `foreign('col', 'fk_x')`.
     *
     * This is not a stylistic alternative, it is the same contract: the
     * framework declares `Blueprint::foreign($columns, $name = null)` and
     * forwards $name into `indexCommand('foreign', ...)`, which resolves
     * `$index = $index ?: $this->createIndexName($type, $columns)`
     * (Blueprint.php:1790). A non-null $name short-circuits that, so no
     * auto name is ever produced — which is why the real dial's
     * 2026_09_14_299999_create_tf_foreign_keys.php:88 was reported as
     * carrying a 101-char "auto" name while it actually declares a 30-char
     * one. Both the array form and the single-column string form are read.
     *
     * @return string|null the raw second argument, or null when absent/blank.
     */
    private static function foreignSecondArgument(string $stmt): ?string
    {
        $pos = strpos($stmt, '->foreign(');
        if ($pos === false) {
            return null;
        }

        $args = self::parenArgument($stmt, $pos + strlen('->foreign('));
        if ($args === null) {
            return null;
        }

        $parts = self::splitTopLevelArguments($args);
        if (count($parts) < 2) {
            return null;
        }

        $name = trim($parts[1]);

        return $name === '' ? null : $name;
    }

    /**
     * Split a raw argument list on top-level commas only. A comma nested in
     * a `[...]` array, a `(...)` call, or a quoted literal is data, not a
     * separator — without this, `foreign(['a','b'], 'name')` splits into
     * three pieces and the name is lost, which is the blind spot all over
     * again.
     *
     * @return list<string>
     */
    private static function splitTopLevelArguments(string $args): array
    {
        $parts = [];
        $current = '';
        $depth = 0;
        $quote = null;
        $len = strlen($args);

        for ($i = 0; $i < $len; $i++) {
            $c = $args[$i];

            if ($quote !== null) {
                $current .= $c;
                if ($c === '\\' && $i + 1 < $len) {
                    $current .= $args[++$i];

                    continue;
                }
                if ($c === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($c === "'" || $c === '"') {
                $quote = $c;
            } elseif ($c === '[' || $c === '(') {
                $depth++;
            } elseif ($c === ']' || $c === ')') {
                $depth--;
            } elseif ($c === ',' && $depth === 0) {
                $parts[] = $current;
                $current = '';

                continue;
            }

            $current .= $c;
        }

        $parts[] = $current;

        return $parts;
    }

    /**
     * @return string|null statically-evaluable FK name from the raw
     *                     ->name(...) argument; null when the expr cannot
     *                     be resolved (best-effort — never a violation).
     */
    private static function evaluateNameArgument(string $arg, string $currentTable): ?string
    {
        $out = '';
        $len = strlen($arg);

        for ($i = 0; $i < $len; $i++) {
            $c = $arg[$i];
            if ($c === "'") {
                for ($j = $i + 1; $j < $len; $j++) {
                    if ($arg[$j] === '\\' && $j + 1 < $len) {
                        $out .= $arg[++$j];

                        continue;
                    }
                    if ($arg[$j] === "'") {
                        break;
                    }
                    $out .= $arg[$j];
                }
                $i = $j;

                continue;
            }
            if (str_starts_with(substr($arg, $i), '$table->getTable()')) {
                $out .= $currentTable;
                $i += strlen('$table->getTable()') - 1;

                continue;
            }
            if ($c === '$' || $c === '-' || $c === '>') {
                return null; // unexpected dynamic construct → skip the check
            }
        }

        return $out;
    }

    /** @return string|null text between the opening paren at $start and its matching close. */
    private static function parenArgument(string $s, int $start): ?string
    {
        $depth = 1;
        $out = '';
        $len = strlen($s);

        for ($i = $start; $i < $len; $i++) {
            if ($s[$i] === '(') {
                $depth++;
            } elseif ($s[$i] === ')') {
                $depth--;
            }
            if ($depth === 0) {
                return $out;
            }
            $out .= $s[$i];
        }

        return null;
    }

    private static function at(int $line, string $message): string
    {
        return 'line '.$line.': '.$message;
    }
}
